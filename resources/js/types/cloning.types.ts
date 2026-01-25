/**
 * Cloning Run Status Types
 */
export type CloningRunStatus =
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'cancelled';

/**
 * Log Level Types
 */
export type CloningRunLogLevel = 'info' | 'warning' | 'error';

/**
 * Cloning Run Log Entry
 */
export interface CloningRunLog {
    id: number;
    run_id: number;
    level: CloningRunLogLevel;
    event_type: string;
    message: string;
    data: Record<string, unknown>;
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
 * Cloning Configuration Model
 */
export interface Cloning {
    id: number;
    user_id: number;
    title: string;
    source_connection_id: number;
    target_connection_id: number;
    anonymization_config: Record<string, unknown> | null;
    schedule: string | null;
    is_scheduled: boolean;
    next_run_at: string | null;
    created_at: string;
    updated_at: string;

    // Relations
    sourceConnection?: DatabaseConnectionPartial;
    targetConnection?: DatabaseConnectionPartial;
    runs?: CloningRun[];
    runs_count?: number;
}

/**
 * Cloning Run Model
 */
export interface CloningRun {
    id: number;
    user_id: number;
    cloning_id: number | null;
    batch_id: string | null;
    status: CloningRunStatus;
    started_at: string | null;
    finished_at: string | null;
    current_step: number;
    total_steps: number;
    progress_percent: number;
    error_message: string | null;
    created_at: string;
    updated_at: string;

    // Relations
    cloning?: Cloning;
    logs?: CloningRunLog[];

    // Batch Progress (from Laravel Batch via Inertia)
    batch_progress?: BatchProgress;
}

/**
 * Batch Progress (embedded in CloningRun via Inertia)
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
    recentRuns: CloningRun[];
    activeRuns: CloningRun[];
    completedRuns: number;
    failedRuns: number;
    totalRuns: number;
    clonings: Cloning[];
}

/**
 * Clonings Index Page Props
 */
export interface CloningsIndexProps {
    clonings: {
        data: Cloning[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}

/**
 * Cloning Show Page Props
 */
export interface CloningShowProps {
    cloning: Cloning;
    runs: CloningRun[];
}

/**
 * Cloning Create/Edit Page Props
 */
export interface CloningFormProps {
    cloning?: Cloning;
    prod_connections: { value: number; label: string }[];
    test_connections: { value: number; label: string }[];
}

/**
 * Cloning Run Show Page Props
 */
export interface CloningRunShowProps {
    run: CloningRun;
    logs: CloningRunLog[];
    isActive: boolean;
}

/**
 * Cloning Runs Index Page Props
 */
export interface CloningRunsIndexProps {
    runs: {
        current_page: number;
        data: CloningRun[];
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: PaginationLink[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };
    hasActiveRuns: boolean;
}

/**
 * Pagination Link
 */
export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}
