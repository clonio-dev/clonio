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
use App\Http\Requests\ValidateTransferRunConnectionsRequest;
use App\Jobs\SynchronizeDatabase;
use App\Models\DatabaseConnection;
use App\Models\TransferRun;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransferRunController extends Controller
{
    public function dashboard(): Response
    {
        $runs = TransferRun::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (TransferRun $run): TransferRun => $this->enrichRunWithBatchProgress($run));

        $activeRuns = TransferRun::query()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['queued', 'processing'])
            ->get()
            ->map(fn (TransferRun $run): TransferRun => $this->enrichRunWithBatchProgress($run));
        $completedRuns = TransferRun::query()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->count();
        $failedRuns = TransferRun::query()
            ->where('user_id', auth()->id())
            ->where('status', 'failed')
            ->count();
        $totalRuns = $activeRuns->count() + $completedRuns + $failedRuns;

        return Inertia::render('Dashboard', [
            'recentRuns' => $runs,
            'activeRuns' => $activeRuns,
            'completedRuns' => $completedRuns,
            'failedRuns' => $failedRuns,
            'totalRuns' => $totalRuns,
        ]);
    }

    public function index(): Response
    {
        $runs = TransferRun::query()
            ->with(['sourceConnection:id,name,type', 'targetConnection:id,name,type'])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        $runs->through(fn (TransferRun $run): TransferRun => $this->enrichRunWithBatchProgress($run));

        $hasActiveRuns = $runs->contains(fn ($run): bool => in_array($run->status->value, ['queued', 'processing']));

        return Inertia::render('transfer-runs/Index', [
            'runs' => $runs,
            'hasActiveRuns' => $hasActiveRuns,
        ]);
    }

    public function create(): Response
    {
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
            'prod_connections' => $prodConnections,
            'test_connections' => $testConnections,
        ]);
    }

    public function validateConnections(ValidateTransferRunConnectionsRequest $request): JsonResponse
    {
        return response()->json([
            'source_connection' => [
                'id' => $request->getSourceConnection()->id,
                'name' => $request->getSourceConnection()->name,
            ],
            'target_connection' => [
                'id' => $request->getTargetConnection()->id,
                'name' => $request->getTargetConnection()->name,
            ],
            'source_schema' => $request->getSourceSchema(),
            'target_schema' => $request->getTargetSchema(),
        ]);
    }

    public function store(StoreTransferRunRequest $request): RedirectResponse
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

        // Load relationships
        $run->load(['sourceConnection:id,name,type', 'targetConnection:id,name,type']);

        // Enrich with batch progress
        $run = $this->enrichRunWithBatchProgress($run);

        // Determine if this is an active run
        $isActive = in_array($run->status->value, ['queued', 'processing']);

        // Get logs ordered by creation time
        $logs = $run->logs()->oldest('created_at')->get();

        return Inertia::render('transfer-runs/Show', [
            'run' => $run,
            'logs' => $logs,
            'isActive' => $isActive,
        ]);
    }

    /**
     * Cancel a running transfer
     */
    public function cancel(TransferRun $run): RedirectResponse
    {
        Gate::authorize('update', $run);

        // $this->transferService->cancelTransfer($run);

        return back()->with('success', 'Transfer cancelled');
    }

    /**
     * Retry a failed transfer
     */
    public function retry(TransferRun $run): RedirectResponse
    {
        Gate::authorize('update', $run);

        // $newRun = $this->transferService->retryTransfer($run);

        return to_route('transfer-runs.show', $newRun)
            ->with('success', 'Transfer retry started');
    }

    /**
     * Export logs as JSON
     */
    public function exportLogs(TransferRun $run)
    {
        Gate::authorize('view', $run);

        // Load relationships
        $run->load(['sourceConnection:id,name,type,host,port,database,username', 'targetConnection:id,name,type,host,port,database,username']);

        $logs = $run->logs()->oldest()
            ->get();

        $filename = "transfer-run-{$run->id}-logs.json";

        return response()->json([
            'run_id' => $run->id,
            'source' => $run->sourceConnection->toArray(),
            'target' => $run->targetConnection->toArray(),
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
