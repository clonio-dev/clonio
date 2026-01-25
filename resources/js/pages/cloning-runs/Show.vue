<script setup lang="ts">
import CloningRunConsole from '@/components/cloning-runs/CloningRunConsole.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { CloningRunShowProps, CloningRunStatus } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle2,
    Clock,
    Database,
    Download,
    Loader2,
    RefreshCw,
    StopCircle,
    Terminal,
    Timer,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<CloningRunShowProps>();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Cloning Runs',
        href: '/cloning-runs',
    },
    {
        title: `Run #${props.run.id}`,
        href: `/cloning-runs/${props.run.id}`,
    },
]);

const isRefreshing = ref(false);
const consoleRef = ref<InstanceType<typeof CloningRunConsole> | null>(null);
let refreshInterval: number | null = null;

const statusConfig: Record<
    CloningRunStatus,
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

const currentStatus = computed(
    () => statusConfig[props.run.status] || statusConfig.cancelled,
);

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

const duration = computed(() => {
    if (!props.run.started_at) return '-';

    const start = new Date(props.run.started_at);
    const end = props.run.finished_at
        ? new Date(props.run.finished_at)
        : new Date();

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

function refreshPage() {
    isRefreshing.value = true;
    router.reload({
        only: ['run', 'logs', 'isActive'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function setupAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    if (props.isActive) {
        refreshInterval = window.setInterval(refreshPage, 100);
    }
}

function exportLogs() {
    window.location.href = `/cloning-runs/${props.run.id}/logs/export`;
}

function cancelRun() {
    if (confirm('Are you sure you want to cancel this cloning run?')) {
        router.post(`/cloning-runs/${props.run.id}/cancel`);
    }
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

onMounted(() => {
    setupAutoRefresh();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Cloning Run #${run.id}`" />

        <div class="px-6 py-6 lg:px-8">
            <!-- Back Button & Header -->
            <div class="mb-6">
                <Button
                    variant="ghost"
                    size="sm"
                    as-child
                    class="mb-4 gap-2 text-muted-foreground hover:text-foreground"
                >
                    <Link href="/cloning-runs">
                        <ArrowLeft class="size-4" />
                        Back to Cloning Runs
                    </Link>
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
                                <h1
                                    class="text-2xl font-semibold tracking-tight text-foreground"
                                >
                                    Cloning Run #{{ run.id }}
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
                                    <span
                                        v-if="run.status === 'processing'"
                                        class="tabular-nums"
                                    >
                                        {{ run.progress_percent }}%
                                    </span>
                                </Badge>
                            </div>
                            <p
                                v-if="run.cloning"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ run.cloning.title }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Auto-refresh indicator -->
                        <div
                            v-if="isActive"
                            class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400"
                        >
                            <div
                                class="size-2 animate-pulse rounded-full bg-emerald-500"
                            />
                            <span>Live (1s)</span>
                        </div>

                        <Button
                            v-if="isActive"
                            variant="outline"
                            size="sm"
                            @click="cancelRun"
                            class="gap-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            <StopCircle class="size-4" />
                            Cancel
                        </Button>

                        <Link
                            v-if="run.status === 'completed'"
                            as="a"
                            :href="`/cloning-runs/${run.id}/audit-log`"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive h-9 px-4 py-2 has-[>svg]:px-3 text-primary underline-offset-4 hover:underline"
                        >
                            Audit Log
                        </Link>

                        <Button
                            variant="outline"
                            size="sm"
                            @click="refreshPage"
                            :disabled="isRefreshing"
                            class="gap-2"
                        >
                            <RefreshCw
                                class="size-4"
                                :class="{ 'animate-spin': isRefreshing }"
                            />
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
                <div
                    class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40"
                >
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        <Database class="size-3.5" />
                        Source
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-foreground">
                            {{ run.cloning?.source_connection?.name || '-' }}
                        </span>
                        <Badge
                            v-if="run.cloning?.source_connection?.type"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ run.cloning.source_connection.type }}
                        </Badge>
                    </div>
                </div>

                <!-- Target -->
                <div
                    class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40"
                >
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        <ArrowRight class="size-3.5" />
                        Target
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-foreground">
                            {{ run.cloning?.target_connection?.name || '-' }}
                        </span>
                        <Badge
                            v-if="run.cloning?.target_connection?.type"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ run.cloning.target_connection.type }}
                        </Badge>
                    </div>
                </div>

                <!-- Timing -->
                <div
                    class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40"
                >
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        <Calendar class="size-3.5" />
                        Started
                    </div>
                    <div class="font-medium text-foreground">
                        {{ formattedStartedAt }}
                    </div>
                </div>

                <!-- Duration -->
                <div
                    class="rounded-xl border border-border/60 bg-card p-4 dark:border-border/40"
                >
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        <Timer class="size-3.5" />
                        Duration
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="font-medium text-foreground tabular-nums"
                            >{{ duration }}</span
                        >
                        <Loader2
                            v-if="isActive"
                            class="size-4 animate-spin text-muted-foreground"
                        />
                    </div>
                </div>
            </div>

            <!-- Progress Bar (for active runs) -->
            <div v-if="isActive" class="mb-6">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Progress</span>
                    <span class="font-medium text-foreground tabular-nums">
                        {{ run.current_step }} / {{ run.total_steps }} steps
                        <span class="text-muted-foreground"
                            >({{ run.progress_percent }}%)</span
                        >
                    </span>
                </div>
                <div
                    class="h-2 w-full overflow-hidden rounded-full bg-muted/60 dark:bg-muted/40"
                >
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
                    <XCircle
                        class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400"
                    />
                    <div>
                        <h3 class="font-medium text-red-800 dark:text-red-300">
                            Cloning Failed
                        </h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                            {{ run.error_message }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Log Console Component -->
            <CloningRunConsole
                ref="consoleRef"
                :logs="logs"
                :run-id="run.id"
                :is-active="isActive"
                :finished-at="run.finished_at"
            />
        </div>
    </AppLayout>
</template>
