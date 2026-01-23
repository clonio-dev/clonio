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
 * Database Connection (partial, for relations)
 */
export interface DatabaseConnectionPartial {
    id: number;
    name: string;
    type: string;
}

/**
 * Cloning (new structure)
 */
export interface CloningPartial {
    id: number;
    title: string;
    sourceConnection?: DatabaseConnectionPartial;
    targetConnection?: DatabaseConnectionPartial;
}

/**
 * Transfer Run Model
 */
export interface TransferRun {
    id: number;
    config_id: number;
    user_id: number;
    cloning_id?: number | null;
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
    cloning?: CloningPartial;
    source_connection?: DatabaseConnectionPartial;
    target_connection?: DatabaseConnectionPartial;

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
 * Cloning (full, for dashboard)
 */
export interface Cloning {
    id: number;
    user_id: number;
    title: string;
    source_connection_id: number;
    target_connection_id: number;
    schedule: string | null;
    is_scheduled: boolean;
    created_at: string;
    updated_at: string;
    sourceConnection?: DatabaseConnectionPartial;
    targetConnection?: DatabaseConnectionPartial;
    runs_count?: number;
}

/**
 * Dashboard Props
 */
export interface DashboardProps {
    recentRuns: TransferRun[];
    activeRuns: TransferRun[];
    completedRuns: number;
    failedRuns: number;
    totalRuns: number;
    clonings?: Cloning[];
}

/**
 * Run Card Props
 */
export interface RunCardProps {
    run: TransferRun;
    isActive: boolean;
}

/**
 * Run Detail Props (legacy)
 */
export interface RunDetailProps {
    runId: number;
    initialRun?: TransferRun;
}

/**
 * Transfer Run Show Page Props
 */
export interface TransferRunShowProps {
    run: TransferRun;
    logs: TransferRunLog[];
    isActive: boolean;
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
