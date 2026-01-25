<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CloningRunStatus;
use App\Models\Cloning;
use App\Models\CloningRun;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CloningRunController extends Controller
{
    /**
     * Display the dashboard with recent runs.
     */
    public function dashboard(): Response
    {
        $runs = CloningRun::query()
            ->with([
                'cloning:id,title,source_connection_id,target_connection_id',
                'cloning.sourceConnection:id,name,type',
                'cloning.targetConnection:id,name,type',
            ])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (CloningRun $run): CloningRun => $this->enrichRunWithBatchProgress($run));

        $activeRuns = CloningRun::query()
            ->with([
                'cloning:id,title,source_connection_id,target_connection_id',
                'cloning.sourceConnection:id,name,type',
                'cloning.targetConnection:id,name,type',
            ])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['queued', 'processing'])
            ->get()
            ->map(fn (CloningRun $run): CloningRun => $this->enrichRunWithBatchProgress($run));

        $completedRuns = CloningRun::query()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->count();

        $failedRuns = CloningRun::query()
            ->where('user_id', auth()->id())
            ->where('status', 'failed')
            ->count();

        $totalRuns = $activeRuns->count() + $completedRuns + $failedRuns;

        $clonings = Cloning::query()
            ->with(['sourceConnection:id,name,type', 'targetConnection:id,name,type'])
            ->where('user_id', auth()->id())
            ->withCount('runs')
            ->latest('id')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'recentRuns' => $runs,
            'activeRuns' => $activeRuns,
            'completedRuns' => $completedRuns,
            'failedRuns' => $failedRuns,
            'totalRuns' => $totalRuns,
            'clonings' => $clonings,
        ]);
    }

    /**
     * Display a listing of runs (optional, can filter by cloning).
     */
    public function index(): Response
    {
        $runs = CloningRun::query()
            ->with(['cloning:id,title,source_connection_id,target_connection_id', 'cloning.sourceConnection:id,name,type', 'cloning.targetConnection:id,name,type'])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        $runs->through(fn (CloningRun $run): CloningRun => $this->enrichRunWithBatchProgress($run));

        $hasActiveRuns = $runs->contains(fn ($run): bool => in_array($run->status->value, ['queued', 'processing']));

        return Inertia::render('cloning-runs/Index', [
            'runs' => $runs,
            'hasActiveRuns' => $hasActiveRuns,
        ]);
    }

    /**
     * Display the specified run.
     */
    public function show(CloningRun $run): Response
    {
        Gate::authorize('view', $run);

        $run->load([
            'cloning:id,title,source_connection_id,target_connection_id',
            'cloning.sourceConnection:id,name,type',
            'cloning.targetConnection:id,name,type',
        ]);

        $run = $this->enrichRunWithBatchProgress($run);

        $isActive = in_array($run->status->value, ['queued', 'processing']);

        $logs = $run->logs()->oldest('id')->get();

        return Inertia::render('cloning-runs/Show', [
            'run' => $run,
            'logs' => $logs,
            'isActive' => $isActive,
        ]);
    }

    public function auditlog(CloningRun $run, AuditService $auditService): View
    {
        $run->loadMissing(['cloning', 'logs', 'user']);
        $verification = $auditService->getVerificationDetails($run);

        return view('reports.audit-trail', [
            'run' => $run,
            'config' => $run->config_snapshot,
            'logs' => $run->logs,
            'verification' => $verification,
        ]);
    }

    /**
     * Cancel a running cloning run.
     */
    public function cancel(CloningRun $run): RedirectResponse
    {
        Gate::authorize('update', $run);

        if ($run->batch_id) {
            $batch = Bus::findBatch($run->batch_id);
            if ($batch && ! $batch->finished()) {
                $batch->cancel();
            }
        }

        $run->update([
            'status' => CloningRunStatus::CANCELLED,
            'finished_at' => now(),
        ]);

        $run->log('batch_cancelled', [
            'processed_before_cancel' => $run->current_step,
        ]);

        return back()->with('success', 'Cloning run cancelled');
    }

    /**
     * Export logs as JSON.
     */
    public function exportLogs(CloningRun $run): StreamedResponse
    {
        Gate::authorize('view', $run);

        $run->load([
            'cloning:id,title,source_connection_id,target_connection_id',
            'cloning.sourceConnection:id,name,type,host,port,database,username',
            'cloning.targetConnection:id,name,type,host,port,database,username',
        ]);

        $logs = $run->logs()->oldest()->get();

        $filename = "cloning-run-{$run->id}-logs.json";

        return response()->streamDownload(function () use ($run, $logs): void {
            echo json_encode([
                'run_id' => $run->id,
                'cloning' => [
                    'id' => $run->cloning->id,
                    'title' => $run->cloning->title,
                ],
                'source' => $run->cloning->sourceConnection->toArray(),
                'target' => $run->cloning->targetConnection->toArray(),
                'exported_at' => now()->toIso8601String(),
                'logs' => $logs,
            ], JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Enrich CloningRun with real-time batch progress.
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
