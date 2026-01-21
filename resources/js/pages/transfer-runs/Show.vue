<script setup lang="ts">
import TransferRunController from '@/actions/App/Http/Controllers/TransferRunController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { LogLevel, RunStatus, TransferRun, TransferRunLog } from '@/types/transfer-run.types';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle2,
    Clock,
    Database,
    Download,
    Info,
    Loader2,
    RefreshCw,
    Terminal,
    Timer,
    TriangleAlert,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    run: TransferRun;
    logs: TransferRunLog[];
    isActive: boolean;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Transfer Runs',
        href: TransferRunController.index().url,
    },
    {
        title: `Run #${props.run.id}`,
        href: `/transfers/${props.run.id}`,
    },
];

const isRefreshing = ref(false);
const logContainerRef = ref<HTMLDivElement | null>(null);
const autoScroll = ref(true);
let refreshInterval: number | null = null;

const statusConfig: Record<
    RunStatus,
    {
        icon: typeof CheckCircle2;
        label: string;
        badgeClass: string;
        iconClass: string;
        bgClass: string;
    }
> = {
    queued: {
        icon: Clock,
        label: 'Queued',
        badgeClass:
            'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900',
        iconClass: 'text-blue-600 dark:text-blue-400',
        bgClass: 'from-blue-500/10 to-blue-600/10',
    },
    processing: {
        icon: Loader2,
        label: 'Running',
        badgeClass:
            'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-900',
        iconClass: 'text-emerald-600 dark:text-emerald-400 animate-spin',
        bgClass: 'from-emerald-500/10 to-teal-500/10',
    },
    completed: {
        icon: CheckCircle2,
        label: 'Completed',
        badgeClass:
            'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/50 dark:text-green-400 dark:border-green-900',
        iconClass: 'text-green-600 dark:text-green-400',
        bgClass: 'from-green-500/10 to-emerald-500/10',
    },
    failed: {
        icon: XCircle,
        label: 'Failed',
        badgeClass:
            'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-900',
        iconClass: 'text-red-600 dark:text-red-400',
        bgClass: 'from-red-500/10 to-rose-500/10',
    },
    cancelled: {
        icon: AlertCircle,
        label: 'Cancelled',
        badgeClass:
            'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-950/50 dark:text-gray-400 dark:border-gray-900',
        iconClass: 'text-gray-600 dark:text-gray-400',
        bgClass: 'from-gray-500/10 to-slate-500/10',
    },
};

const logLevelConfig: Record<
    LogLevel,
    {
        icon: typeof Info;
        class: string;
        labelClass: string;
    }
> = {
    info: {
        icon: Info,
        class: 'text-sky-400',
        labelClass: 'text-sky-500',
    },
    warning: {
        icon: TriangleAlert,
        class: 'text-amber-400',
        labelClass: 'text-amber-500',
    },
    error: {
        icon: XCircle,
        class: 'text-red-400',
        labelClass: 'text-red-500',
    },
};

const currentStatus = computed(() => statusConfig[props.run.status] || statusConfig.cancelled);

const formattedStartedAt = computed(() => {
    if (!props.run.started_at) return 'Not started';
    return new Date(props.run.started_at).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

const formattedFinishedAt = computed(() => {
    if (!props.run.finished_at) return props.isActive ? 'In progress...' : '-';
    return new Date(props.run.finished_at).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

const duration = computed(() => {
    if (!props.run.started_at) return '-';

    const start = new Date(props.run.started_at);
    const end = props.run.finished_at ? new Date(props.run.finished_at) : new Date();

    const diffMs = end.getTime() - start.getTime();
    const hours = Math.floor(diffMs / 3600000);
    const mins = Math.floor((diffMs % 3600000) / 60000);
    const secs = Math.floor((diffMs % 60000) / 1000);

    if (hours > 0) {
        return `${hours}h ${mins}m ${secs}s`;
    }
    if (mins > 0) {
        return `${mins}m ${secs}s`;
    }
    return `${secs}s`;
});

const logStats = computed(() => {
    const stats = { info: 0, warning: 0, error: 0 };
    props.logs.forEach((log) => {
        if (log.level in stats) {
            stats[log.level]++;
        }
    });
    return stats;
});

function formatLogTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        fractionalSecondDigits: 3,
    });
}

function getLogLevelConfig(level: LogLevel) {
    return logLevelConfig[level] || logLevelConfig.info;
}

function refreshPage() {
    isRefreshing.value = true;
    router.reload({
        only: ['run', 'logs', 'isActive'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
            if (autoScroll.value) {
                scrollToBottom();
            }
        },
    });
}

function scrollToBottom() {
    nextTick(() => {
        if (logContainerRef.value) {
            logContainerRef.value.scrollTop = logContainerRef.value.scrollHeight;
        }
    });
}

function handleScroll() {
    if (!logContainerRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = logContainerRef.value;
    // If user scrolled up more than 100px from bottom, disable auto-scroll
    autoScroll.value = scrollHeight - scrollTop - clientHeight < 100;
}

function setupAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    if (props.isActive) {
        // High frequency refresh for active runs (every 1 second)
        refreshInterval = window.setInterval(refreshPage, 1000);
    }
}

function exportLogs() {
    window.location.href = `/transfers/${props.run.id}/logs/export`;
}

watch(
    () => props.isActive,
    (isActive) => {
        if (!isActive && refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        } else if (isActive) {
            setupAutoRefresh();
        }
    },
);

watch(
    () => props.logs.length,
    () => {
        if (autoScroll.value) {
            scrollToBottom();
        }
    },
);

onMounted(() => {
    setupAutoRefresh();
    scrollToBottom();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Transfer Run #${run.id}`" />

        <div class="px-6 py-6 lg:px-8">
            <!-- Back Button & Header -->
            <div class="mb-6">
                <Button
                    variant="ghost"
                    size="sm"
                    as-child
                    class="mb-4 gap-2 text-muted-foreground hover:text-foreground"
                >
                    <a :href="TransferRunController.index().url">
                        <ArrowLeft class="size-4" />
                        Back to Transfer Runs
                    </a>
                </Button>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br ring-1 ring-black/5 dark:ring-white/10"
                            :class="currentStatus.bgClass"
                        >
                            <Terminal class="size-6 text-foreground/80" />
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                                    Transfer Run #{{ run.id }}
                                </h1>
                                <Badge
                                    variant="outline"
                                    class="gap-1.5 text-sm font-medium"
                                    :class="currentStatus.badgeClass"
                                >
                                    <component
                                        :is="currentStatus.icon"
                                        class="size-4"
                                        :class="currentStatus.iconClass"
                                    />
                                    {{ currentStatus.label }}
                                    <span v-if="run.status === 'processing'" class="tabular-nums">
                                        {{ run.progress_percent }}%
                                    </span>
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Audit log for compliance and data privacy analysis
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Auto-refresh indicator -->
                        <div
                            v-if="isActive"
                            class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400"
                        >
                            <div class="size-2 animate-pulse rounded-full bg-emerald-500" />
                            <span>Live (1s)</span>
                        </div>

                        <Button
                            variant="outline"
                            size="sm"
                            @click="refreshPage"
                            :disabled="isRefreshing"
                            class="gap-2"
                        >
                            <RefreshCw class="size-4" :class="{ 'animate-spin': isRefreshing }" />
                            Refresh
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            @click="exportLogs"
                            class="gap-2"
                        >
                            <Download class="size-4" />
                            Export
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Source -->
                <div class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        <Database class="size-3.5" />
                        Source
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-foreground">
                            {{ run.source_connection?.name || '-' }}
                        </span>
                        <Badge v-if="run.source_connection?.type" variant="secondary" class="text-xs">
                            {{ run.source_connection.type }}
                        </Badge>
                    </div>
                </div>

                <!-- Target -->
                <div class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        <ArrowRight class="size-3.5" />
                        Target
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-foreground">
                            {{ run.target_connection?.name || '-' }}
                        </span>
                        <Badge v-if="run.target_connection?.type" variant="secondary" class="text-xs">
                            {{ run.target_connection.type }}
                        </Badge>
                    </div>
                </div>

                <!-- Timing -->
                <div class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        <Calendar class="size-3.5" />
                        Started
                    </div>
                    <div class="font-medium text-foreground">
                        {{ formattedStartedAt }}
                    </div>
                </div>

                <!-- Duration -->
                <div class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        <Timer class="size-3.5" />
                        Duration
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="tabular-nums font-medium text-foreground">{{ duration }}</span>
                        <Loader2 v-if="isActive" class="size-4 animate-spin text-muted-foreground" />
                    </div>
                </div>
            </div>

            <!-- Progress Bar (for active runs) -->
            <div v-if="isActive" class="mb-6">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Progress</span>
                    <span class="tabular-nums font-medium text-foreground">
                        {{ run.current_step }} / {{ run.total_steps }} steps
                        <span class="text-muted-foreground">({{ run.progress_percent }}%)</span>
                    </span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-muted/60 dark:bg-muted/40">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                        :style="{ width: `${run.progress_percent}%` }"
                    />
                </div>
            </div>

            <!-- Error Message (if failed) -->
            <div
                v-if="run.status === 'failed' && run.error_message"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30"
            >
                <div class="flex items-start gap-3">
                    <XCircle class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                    <div>
                        <h3 class="font-medium text-red-800 dark:text-red-300">Transfer Failed</h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                            {{ run.error_message }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Log Console -->
            <div class="overflow-hidden rounded-xl border border-border/60 dark:border-border/40">
                <!-- Console Header -->
                <div class="flex items-center justify-between border-b border-zinc-800 bg-zinc-900 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <div class="size-3 rounded-full bg-red-500/80"></div>
                            <div class="size-3 rounded-full bg-yellow-500/80"></div>
                            <div class="size-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <span class="font-mono text-sm text-zinc-400">
                            transfer-run-{{ run.id }}.log
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-xs">
                        <div class="flex items-center gap-1.5 text-sky-400">
                            <Info class="size-3.5" />
                            <span class="tabular-nums">{{ logStats.info }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-amber-400">
                            <TriangleAlert class="size-3.5" />
                            <span class="tabular-nums">{{ logStats.warning }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-red-400">
                            <XCircle class="size-3.5" />
                            <span class="tabular-nums">{{ logStats.error }}</span>
                        </div>
                    </div>
                </div>

                <!-- Console Output -->
                <div
                    ref="logContainerRef"
                    class="h-[500px] overflow-y-auto bg-zinc-950 p-4 font-mono text-sm"
                    @scroll="handleScroll"
                >
                    <!-- Empty State -->
                    <div
                        v-if="logs.length === 0"
                        class="flex h-full items-center justify-center text-zinc-500"
                    >
                        <div class="text-center">
                            <Terminal class="mx-auto mb-3 size-10 opacity-50" />
                            <p>Waiting for log entries...</p>
                            <p v-if="isActive" class="mt-1 text-xs text-zinc-600">
                                Logs will appear here as the transfer progresses
                            </p>
                        </div>
                    </div>

                    <!-- Log Entries -->
                    <div v-else class="space-y-1">
                        <div
                            v-for="log in logs"
                            :key="log.id"
                            class="group flex items-start gap-3 rounded px-2 py-1 transition-colors hover:bg-zinc-900"
                        >
                            <!-- Timestamp -->
                            <span class="shrink-0 text-zinc-600">
                                [{{ formatLogTime(log.created_at) }}]
                            </span>

                            <!-- Level Badge -->
                            <span
                                class="w-16 shrink-0 text-right font-semibold uppercase"
                                :class="getLogLevelConfig(log.level).labelClass"
                            >
                                {{ log.level }}
                            </span>

                            <!-- Event Type -->
                            <span class="shrink-0 text-violet-400">
                                {{ log.event_type }}
                            </span>

                            <!-- Message -->
                            <span class="text-zinc-300">
                                {{ log.message }}
                            </span>

                            <!-- Data (if present and has content) -->
                            <span
                                v-if="log.data && Object.keys(log.data).length > 0"
                                class="shrink-0 text-zinc-600"
                            >
                                {{ JSON.stringify(log.data) }}
                            </span>
                        </div>

                        <!-- Cursor / Active Indicator -->
                        <div v-if="isActive" class="flex items-center gap-2 px-2 py-1">
                            <span class="animate-pulse text-emerald-400">_</span>
                        </div>
                    </div>
                </div>

                <!-- Console Footer -->
                <div class="flex items-center justify-between border-t border-zinc-800 bg-zinc-900 px-4 py-2 text-xs text-zinc-500">
                    <span>{{ logs.length }} log entries</span>
                    <div class="flex items-center gap-4">
                        <button
                            v-if="!autoScroll && isActive"
                            @click="autoScroll = true; scrollToBottom()"
                            class="text-emerald-400 hover:text-emerald-300"
                        >
                            Resume auto-scroll
                        </button>
                        <span v-if="run.finished_at">
                            Completed: {{ formattedFinishedAt }}
                        </span>
                        <span v-else-if="isActive" class="flex items-center gap-1.5">
                            <Loader2 class="size-3 animate-spin" />
                            Processing...
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
