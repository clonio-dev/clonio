<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clonio\ExecuteCloning;
use App\Enums\CloningRunStatus;
use App\Http\Requests\StoreCloningRequest;
use App\Http\Requests\UpdateCloningRequest;
use App\Http\Requests\ValidateCloningConnectionsRequest;
use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
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
    public function index(#[CurrentUser] User $user): Response
    {
        $clonings = Cloning::query()
            ->forUser($user->id)
            ->with(['sourceConnection:id,name,type', 'targetConnection:id,name,type', 'lastRun'])
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
    public function store(#[CurrentUser] User $user, StoreCloningRequest $request, ExecuteCloning $executeCloning): RedirectResponse
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
            $run = $executeCloning->start($cloning);
            $run->log('user_initiated', ['message' => 'Cloning initiated by user ' . $user->name, 'name' => $user->name, 'email' => $user->email]);

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
    public function execute(#[CurrentUser] User $user, Cloning $cloning, ExecuteCloning $executeCloning): RedirectResponse
    {
        Gate::authorize('update', $cloning);

        $run = $executeCloning->start($cloning);
        $run->log('user_initiated', ['message' => 'Cloning initiated by user ' . $user->name, 'name' => $user->name, 'email' => $user->email]);

        return to_route('cloning-runs.show', ['run' => $run])
            ->with('success', 'Cloning execution started');
    }

    /**
     * Pause a scheduled cloning.
     */
    public function pause(Cloning $cloning): RedirectResponse
    {
        Gate::authorize('update', $cloning);

        $cloning->pause();

        return back()->with('success', 'Cloning paused');
    }

    /**
     * Resume a paused cloning.
     */
    public function resume(Cloning $cloning): RedirectResponse
    {
        Gate::authorize('update', $cloning);

        $cloning->resume();

        return back()->with('success', 'Cloning resumed');
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
