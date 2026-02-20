import { PaginatedData } from '@/types/index';

/**
 * Cloning Run Status Types
 */
export type CloningRunStatus =
    | 'queued'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'cancelled';

export type CloningRunInitiator = 'user' | 'api' | 'scheduler' | 'manual';

/**
 * Log Level Types
 */
export type CloningRunLogLevel =
    | 'info'
    | 'warning'
    | 'error'
    | 'success'
    | 'debug';

/**
 * Progress data for table_transfer_progress events
 */
export interface TableTransferProgressData {
    table: string;
    rows_processed: number;
    total_rows: number;
    percent: number;
    rows_per_second?: number;
    elapsed_seconds?: number;
    estimated_seconds_remaining?: number | null;
}

/**
 * Cloning Run Log Entry
 */
export interface CloningRunLog {
    id: number;
    run_id: number;
    level: CloningRunLogLevel;
    event_type: string;
    message: string;
    data: Record<string, unknown> | TableTransferProgressData;
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
    trigger_config: TriggerConfig | null;
    api_trigger_token: string | null;
    is_scheduled: boolean;
    is_paused: boolean;
    consecutive_failures: number;
    next_run_at: string | null;
    created_at: string;
    updated_at: string;

    // Relations
    source_connection?: DatabaseConnectionPartial;
    target_connection?: DatabaseConnectionPartial;
    runs?: CloningRun[];
    runs_count?: number;
}

/**
 * Webhook Result Entry (stored on cloning_runs.webhook_results)
 */
export interface WebhookResult {
    status: 'success' | 'failed';
    event: string;
    url: string;
    http_status?: number;
    error?: string;
    attempt?: number;
    message: string;
    timestamp: string;
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
    initiator: CloningRunInitiator;
    started_at: string | null;
    finished_at: string | null;
    current_step: number;
    total_steps: number;
    progress_percent: number;
    error_message: string | null;
    webhook_results: WebhookResult[] | null;
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
    clonings: PaginatedData<Cloning & { last_run: CloningRun | null }>;
}

/**
 * Cloning Show Page Props
 */
export interface TriggerConfig {
    apiTrigger: { enabled: boolean };
    webhookOnSuccess: {
        enabled: boolean;
        url: string;
        method: string;
        headers: Record<string, string>;
        secret: string;
    };
    webhookOnFailure: {
        enabled: boolean;
        url: string;
        method: string;
        headers: Record<string, string>;
        secret: string;
    };
}

export interface CloningShowProps {
    cloning: Cloning;
    runs: CloningRun[];
    estimatedDuration?: number | null;
    api_trigger_url: string | null;
    lastAuditLogUrl: string | null;
}

/**
 * Cloning Create/Edit Page Props
 */
export interface CloningFormProps {
    cloning?: Cloning;
    prod_connections: { value: number; label: string; type: string }[];
    test_connections: { value: number; label: string; type: string }[];
}

/**
 * Cloning Run Show Page Props
 */
export interface CloningRunShowProps {
    run: CloningRun & {
        cloning: Cloning & {
            source_connection: DatabaseConnectionPartial;
            target_connection: DatabaseConnectionPartial;
        };
    };
    logs: CloningRunLog[];
    isActive: boolean;
}

/**
 * Cloning Runs Index Page Props
 */
export interface CloningRunsIndexProps {
    runs: PaginatedData<CloningRun>;
    hasActiveRuns: boolean;
}
