/**
 * Transfer Run Status Types
 */
export type RunStatus =
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'cancelled';

/**
 * Log Level Types
 */
export type LogLevel = 'info' | 'warning' | 'error';

/**
 * Transfer Run Log Entry
 */
export interface TransferRunLog {
    id: number;
    run_id: number;
    level: LogLevel;
    event_type: string;
    message: string;
    data: Record<string, any>;
    created_at: string;
}

/**
 * Transfer Run Model
 */
export interface TransferRun {
    id: number;
    config_id: number;
    user_id: number;
    batch_id: string | null;
    status: RunStatus;
    started_at: string | null;
    finished_at: string | null;
    current_step: number;
    total_steps: number;
    progress_percent: number;
    error_message: string | null;
    config_snapshot: {
        name: string;
        source_connection: {
            name: string;
            type: string;
        };
        target_connection: {
            name: string;
            type: string;
        };
    } | null;
    created_at: string;
    updated_at: string;

    // Relations
    logs?: TransferRunLog[];
    config?: {
        id: number;
        name: string;
    };

    // Batch Progress (from Laravel Batch via Inertia)
    batch_progress?: BatchProgress;
}

/**
 * Batch Progress (embedded in TransferRun via Inertia)
 */
export interface BatchProgress {
    processed_jobs: number;
    pending_jobs: number;
    failed_jobs: number;
    total_jobs: number;
    finished: boolean;
    cancelled: boolean;
}

/**
 * Dashboard Props
 */
export interface DashboardProps {
    runs: TransferRun[];
    hasActiveRuns: boolean;
}

/**
 * Run Card Props
 */
export interface RunCardProps {
    run: TransferRun;
    isActive: boolean;
}

/**
 * Run Detail Props
 */
export interface RunDetailProps {
    runId: number;
    initialRun?: TransferRun;
}

/**
 * Status Badge Props
 */
export interface StatusBadgeProps {
    status: RunStatus;
    progress?: number;
}

/**
 * Run Log Props
 */
export interface RunLogProps {
    runId: number;
    initialLogs?: TransferRunLog[];
}
