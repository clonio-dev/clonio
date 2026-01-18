<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ColumnMutationData;
use App\Data\ColumnMutationDataOptions;
use App\Data\ColumnMutationStrategyEnum;
use App\Data\SynchronizationOptionsData;
use App\Data\TableAnonymizationOptionsData;
use App\Enums\TransferRunStatus;
use App\Http\Requests\StoreTransferRunRequest;
use App\Jobs\SynchronizeDatabase;
use App\Models\DatabaseConnection;
use App\Models\TransferRun;
use Illuminate\Bus\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransferRunController extends Controller
{
    public $transferService;

    public function index(): Response
    {
        $runs = TransferRun::query()
//            ->with(['config:id,name', 'logs'])
            ->where('user_id', auth()->id())
            ->latest('started_at')
            ->limit(20)
            ->get()
            ->map(fn (TransferRun $run): TransferRun => $this->enrichRunWithBatchProgress($run));

        $hasActiveRuns = $runs->contains(fn ($run): false => in_array($run->status, ['queued', 'processing'])
        );

        return Inertia::render('Dashboard', [
            'runs' => $runs,
            'hasActiveRuns' => $hasActiveRuns,
        ]);
    }

    public function create(): Response
    {
        /*$c = DatabaseConnection::query()->create([
            'user_id' => auth()->id(),
            'name' => 'Local Prod DB',
            'type' => DatabaseConnectionTypes::SQLITE,
            'host' => '',
            'port' => 0,
            'database' => database_path('database.sqlite'),
            'username' => '',
            'password' => 'password',
            'is_production_stage' => true,
        ]);
        $c2 = DatabaseConnection::query()->create([
            'user_id' => auth()->id(),
            'name' => 'Local Test DB',
            'type' => DatabaseConnectionTypes::SQLITE,
            'host' => '',
            'port' => 0,
            'database' => database_path('test.sqlite'),
            'username' => '',
            'password' => 'password',
            'is_production_stage' => false,
        ]);*/

        $prodConnections = DatabaseConnection::query()
            ->forUser(auth()->id())
            ->prodDatabases()
            ->get(['id', 'name'])
            ->map(fn ($c): array => ['value' => $c->id, 'label' => $c->name]);

        $testConnections = DatabaseConnection::query()
            ->forUser(auth()->id())
            ->testDatabases()
            ->get(['id', 'name'])
            ->map(fn ($c): array => ['value' => $c->id, 'label' => $c->name]);

        return Inertia::render('transfer-runs/Create', [
            'prod_connections' => Inertia::defer(fn () => $prodConnections),
            'test_connections' => Inertia::defer(fn () => $testConnections),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransferRunRequest $request): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {
        /** @var TransferRun $transferRun */
        $transferRun = TransferRun::query()->create([
            'user_id' => $request->user()->id,
            'source_connection_id' => $request->validated('source_connection_id'),
            'target_connection_id' => $request->validated('target_connection_id'),
            'script' => $request->validated('script'),
            'batch_id' => null,
            'status' => TransferRunStatus::QUEUED,
            'started_at' => null,
        ]);

        $transferRun->log('transfer_run_created');

        $synchronizationConfigData = new SynchronizationOptionsData(
            tableAnonymizationOptions: new Collection([
                new TableAnonymizationOptionsData(
                    tableName: 'products',
                    columnMutations: new Collection([
                        new ColumnMutationData(
                            columnName: 'name',
                            strategy: ColumnMutationStrategyEnum::MASK,
                            options: new ColumnMutationDataOptions(
                                visibleChars: 2,
                                maskChar: '#',
                            ),
                        ),
                        new ColumnMutationData(
                            columnName: 'in_stock',
                            strategy: ColumnMutationStrategyEnum::STATIC,
                            options: new ColumnMutationDataOptions(
                                value: 3,
                            ),
                        ),
                    ])
                ),
            ]),
        );

        $connectionDataSource = $transferRun->sourceConnection->toConnectionDataDto();
        $connectionDataTarget = $transferRun->targetConnection->toConnectionDataDto();

        $batch = Bus::batch([
            new SynchronizeDatabase($synchronizationConfigData, $connectionDataSource, $connectionDataTarget, $transferRun),
        ])
            ->name('Synchronize database ' . $connectionDataSource->name)
            ->before(function (Batch $batch) use ($transferRun): void {
                // store the batch status or notify
                $transferRun->update(['batch_id' => $batch->id, 'started_at' => now()]);
            })
            ->then(function (Batch $batch) use ($transferRun): void {
                // store the batch status or notify
                $transferRun->update(['status' => TransferRunStatus::COMPLETED, 'finished_at' => now()]);
            })
            ->progress(function (Batch $batch) use ($transferRun): void {
                $transferRun->update([
                    'status' => TransferRunStatus::PROCESSING,
                    'progress_percent' => $batch->progress(),
                    'current_step' => $batch->processedJobs(),
                    'total_steps' => $batch->totalJobs,
                ]);
            })
            ->finally(function (Batch $batch) use ($transferRun): void {
                if ($batch->cancelled()) {
                    $transferRun->update(['status' => TransferRunStatus::CANCELLED]);
                }
                if ($batch->hasFailures()) {
                    $transferRun->update(['status' => TransferRunStatus::FAILED,  'finished_at' => now()]);
                }
            })
            ->dispatch();

        return to_route('batch.show', ['batch' => $batch->id]);
    }

    public function show(TransferRun $run): Response
    {
        Gate::authorize('view', $run);

        $run->load(['config:id,name', 'logs']);

        // Enrich with batch progress
        $run = $this->enrichRunWithBatchProgress($run);

        return Inertia::render('TransferRunDetail', [
            'run' => $run,
        ]);
    }

    /**
     * Cancel a running transfer
     */
    public function cancel(TransferRun $run): RedirectResponse
    {
        $this->authorize('cancel', $run);

        $this->transferService->cancelTransfer($run);

        return back()->with('success', 'Transfer cancelled');
    }

    /**
     * Retry a failed transfer
     */
    public function retry(TransferRun $run): RedirectResponse
    {
        $this->authorize('retry', $run);

        $newRun = $this->transferService->retryTransfer($run);

        return to_route('transfer-runs.show', $newRun)
            ->with('success', 'Transfer retry started');
    }

    /**
     * Export logs as JSON
     */
    public function exportLogs(TransferRun $run)
    {
        $this->authorize('view', $run);

        $logs = $run->logs()->oldest()
            ->get();

        $filename = "transfer-run-{$run->id}-logs.json";

        return response()->json([
            'run_id' => $run->id,
            'config_name' => $run->config->name,
            'exported_at' => now()->toIso8601String(),
            'logs' => $logs,
        ])
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Enrich TransferRun with real-time batch progress
     */
    private function enrichRunWithBatchProgress(TransferRun $run): TransferRun
    {
        if (! $run->batch_id) {
            return $run;
        }

        $batch = Bus::findBatch($run->batch_id);

        if ($batch) {
            $run->batch_progress = [
                'processed_jobs' => $batch->processedJobs(),
                'pending_jobs' => $batch->pendingJobs,
                'failed_jobs' => $batch->failedJobs,
                'total_jobs' => $batch->totalJobs,
                'finished' => $batch->finished(),
                'cancelled' => $batch->cancelled(),
            ];

            // Sync status from batch if needed
            if ($batch->finished() && $run->status === 'processing') {
                $run->status = $batch->failedJobs > 0 ? 'failed' : 'completed';
            }
        }

        return $run;
    }
}
