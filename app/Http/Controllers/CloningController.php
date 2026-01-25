<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\SynchronizationOptionsData;
use App\Enums\CloningRunStatus;
use App\Http\Requests\StoreCloningRequest;
use App\Http\Requests\UpdateCloningRequest;
use App\Http\Requests\ValidateCloningConnectionsRequest;
use App\Jobs\SynchronizeDatabase;
use App\Jobs\TestConnection;
use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\DatabaseConnection;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CloningController extends Controller
{
    /**
     * Display a listing of clonings.
     */
    public function index(): Response
    {
        $clonings = Cloning::query()
            ->with(['sourceConnection:id,name,type', 'targetConnection:id,name,type'])
            ->where('user_id', auth()->id())
            ->withCount('runs')
            ->latest('id')
            ->paginate(10);

        return Inertia::render('clonings/Index', [
            'clonings' => $clonings,
        ]);
    }

    /**
     * Show the form for creating a new cloning.
     */
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

        return Inertia::render('clonings/Create', [
            'prod_connections' => $prodConnections,
            'test_connections' => $testConnections,
        ]);
    }

    /**
     * Validate connections and return schema for the create wizard.
     */
    public function validateConnections(ValidateCloningConnectionsRequest $request): RedirectResponse
    {
        return back()->with('validated_connections', [
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

    /**
     * Store a newly created cloning and optionally execute it.
     */
    public function store(StoreCloningRequest $request): RedirectResponse
    {
        $anonymizationConfigJson = $request->validated('anonymization_config');
        $anonymizationConfig = $anonymizationConfigJson ? json_decode((string) $anonymizationConfigJson, true) : null;

        $isScheduled = $request->boolean('is_scheduled', false);
        $schedule = $isScheduled ? $request->validated('schedule') : null;

        /** @var Cloning $cloning */
        $cloning = Cloning::query()->create([
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'source_connection_id' => $request->validated('source_connection_id'),
            'target_connection_id' => $request->validated('target_connection_id'),
            'anonymization_config' => $anonymizationConfig,
            'schedule' => $schedule,
            'is_scheduled' => $isScheduled,
            'next_run_at' => Cloning::calculateNextRunAt($schedule),
        ]);

        // If execute_now is true, trigger an immediate run
        if ($request->boolean('execute_now', true)) {
            $run = $this->executeCloning($cloning);

            return to_route('cloning-runs.show', ['run' => $run])
                ->with('success', 'Cloning created and execution started');
        }

        return to_route('clonings.show', ['cloning' => $cloning])
            ->with('success', 'Cloning configuration saved');
    }

    /**
     * Display the specified cloning with its runs.
     */
    public function show(Cloning $cloning): Response
    {
        Gate::authorize('view', $cloning);

        $cloning->load(['sourceConnection:id,name,type', 'targetConnection:id,name,type']);

        $runs = $cloning->runs()
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (CloningRun $run): CloningRun => $this->enrichRunWithBatchProgress($run));

        return Inertia::render('clonings/Show', [
            'cloning' => $cloning,
            'runs' => $runs,
        ]);
    }

    /**
     * Show the form for editing the specified cloning.
     */
    public function edit(Cloning $cloning): Response
    {
        Gate::authorize('update', $cloning);

        $cloning->load(['sourceConnection:id,name,type', 'targetConnection:id,name,type']);

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

        return Inertia::render('clonings/Edit', [
            'cloning' => $cloning,
            'prod_connections' => $prodConnections,
            'test_connections' => $testConnections,
        ]);
    }

    /**
     * Update the specified cloning.
     */
    public function update(UpdateCloningRequest $request, Cloning $cloning): RedirectResponse
    {
        Gate::authorize('update', $cloning);

        $anonymizationConfigJson = $request->validated('anonymization_config');
        $anonymizationConfig = $anonymizationConfigJson ? json_decode((string) $anonymizationConfigJson, true) : null;

        $isScheduled = $request->boolean('is_scheduled', false);
        $schedule = $isScheduled ? $request->validated('schedule') : null;

        $cloning->update([
            'title' => $request->validated('title'),
            'source_connection_id' => $request->validated('source_connection_id'),
            'target_connection_id' => $request->validated('target_connection_id'),
            'anonymization_config' => $anonymizationConfig,
            'schedule' => $schedule,
            'is_scheduled' => $isScheduled,
            'next_run_at' => Cloning::calculateNextRunAt($schedule),
        ]);

        return to_route('clonings.show', ['cloning' => $cloning])
            ->with('success', 'Cloning configuration updated');
    }

    /**
     * Remove the specified cloning.
     */
    public function destroy(Cloning $cloning): RedirectResponse
    {
        Gate::authorize('delete', $cloning);

        $cloning->delete();

        return to_route('clonings.index')
            ->with('success', 'Cloning deleted');
    }

    /**
     * Execute the cloning immediately.
     */
    public function execute(Cloning $cloning): RedirectResponse
    {
        Gate::authorize('update', $cloning);

        $run = $this->executeCloning($cloning);

        return to_route('cloning-runs.show', ['run' => $run])
            ->with('success', 'Cloning execution started');
    }

    /**
     * Execute a cloning and return the created run.
     */
    private function executeCloning(Cloning $cloning): CloningRun
    {
        /** @var CloningRun $run */
        $run = CloningRun::query()->create([
            'user_id' => $cloning->user_id,
            'cloning_id' => $cloning->id,
            'batch_id' => null,
            'status' => CloningRunStatus::QUEUED,
            'started_at' => null,
        ]);

        $run->log('cloning_run_created');

        $connectionDataSource = $cloning->sourceConnection->toConnectionDataDto();
        $connectionDataTarget = $cloning->targetConnection->toConnectionDataDto();

        Bus::batch([
            new TestConnection($cloning->sourceConnection, $run),
            new TestConnection($cloning->targetConnection, $run),
            new SynchronizeDatabase(
                options: SynchronizationOptionsData::from($cloning->anonymization_config),
                sourceConnectionData: $connectionDataSource,
                targetConnectionData: $connectionDataTarget,
                run: $run,
            ),
        ])
            ->name('Synchronize database ' . $connectionDataSource->name)
            ->before(function (Batch $batch) use ($run): void {
                $run->update(['batch_id' => $batch->id, 'started_at' => now()]);
            })
            ->then(function (Batch $batch) use ($run): void {
                $run->update(['status' => CloningRunStatus::COMPLETED, 'finished_at' => now()]);
            })
            ->progress(function (Batch $batch) use ($run): void {
                $run->update([
                    'status' => CloningRunStatus::PROCESSING,
                    'progress_percent' => $batch->progress(),
                    'current_step' => $batch->processedJobs(),
                    'total_steps' => $batch->totalJobs,
                ]);
            })
            ->finally(function (Batch $batch) use ($run): void {
                if ($batch->cancelled()) {
                    $run->update(['status' => CloningRunStatus::CANCELLED]);
                }
                if ($batch->hasFailures()) {
                    $run->update(['status' => CloningRunStatus::FAILED, 'finished_at' => now()]);
                }
            })
            ->dispatch();

        return $run;
    }

    /**
     * Enrich CloningRun with real-time batch progress
     */
    private function enrichRunWithBatchProgress(CloningRun $run): CloningRun
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

            if ($batch->finished() && $run->status === CloningRunStatus::PROCESSING) {
                $run->status = $batch->failedJobs > 0 ? CloningRunStatus::FAILED : CloningRunStatus::COMPLETED;
            }
        }

        return $run;
    }
}
