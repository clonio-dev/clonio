<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { RunCardProps } from '@/types/transfer-run.types';
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Calendar,
    CheckCircle2,
    Clock,
    Database,
    Loader2,
    Timer,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<RunCardProps>();

const formattedStartedAt = computed(() => {
    if (!props.run.started_at) return 'Not started';
    return new Date(props.run.started_at).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const duration = computed(() => {
    if (!props.run.started_at) return null;

    const start = new Date(props.run.started_at);
    const end = props.run.finished_at
        ? new Date(props.run.finished_at)
        : new Date();

    const diffMs = end.getTime() - start.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffSecs = Math.floor((diffMs % 60000) / 1000);

    if (diffMins > 0) {
        return `${diffMins}m ${diffSecs}s`;
    }
    return `${diffSecs}s`;
});

const configName = computed(() => {
    // Support both old (config) and new (cloning) structure
    return (
        props.run.cloning?.title ||
        props.run.config?.name ||
        props.run.config_snapshot?.name ||
        'Unknown Config'
    );
});

const sourceTarget = computed(() => {
    // Support new structure (cloning with connections)
    const cloning = props.run.cloning as
        | {
              sourceConnection?: { name: string; type: string };
              targetConnection?: { name: string; type: string };
          }
        | undefined;

    if (cloning?.sourceConnection && cloning?.targetConnection) {
        return {
            source: cloning.sourceConnection.name,
            sourceType: cloning.sourceConnection.type,
            target: cloning.targetConnection.name,
            targetType: cloning.targetConnection.type,
        };
    }

    // Fallback to old structure (config_snapshot)
    const snapshot = props.run.config_snapshot;
    if (!snapshot) return null;

    return {
        source: snapshot.source_connection.name,
        sourceType: snapshot.source_connection.type,
        target: snapshot.target_connection.name,
        targetType: snapshot.target_connection.type,
    };
});

const statusConfig = computed(() => {
    const configs = {
        queued: {
            icon: Clock,
            label: 'Queued',
            badgeClass:
                'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900',
            cardClass: '',
            iconClass: 'text-blue-600 dark:text-blue-400',
        },
        processing: {
            icon: Loader2,
            label: 'Running',
            badgeClass:
                'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-900',
            cardClass: 'ring-2 ring-emerald-500/30 dark:ring-emerald-500/20',
            iconClass: 'text-emerald-600 dark:text-emerald-400 animate-spin',
        },
        completed: {
            icon: CheckCircle2,
            label: 'Completed',
            badgeClass:
                'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/50 dark:text-green-400 dark:border-green-900',
            cardClass: '',
            iconClass: 'text-green-600 dark:text-green-400',
        },
        failed: {
            icon: XCircle,
            label: 'Failed',
            badgeClass:
                'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-900',
            cardClass: '',
            iconClass: 'text-red-600 dark:text-red-400',
        },
        cancelled: {
            icon: AlertCircle,
            label: 'Cancelled',
            badgeClass:
                'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-950/50 dark:text-gray-400 dark:border-gray-900',
            cardClass: '',
            iconClass: 'text-gray-600 dark:text-gray-400',
        },
    };

    return configs[props.run.status] || configs.cancelled;
});

const isProcessing = computed(() =>
    ['processing', 'queued'].includes(props.run.status),
);

function openRunDetail() {
    router.visit(`/cloning-runs/${props.run.id}`);
}
</script>

<template>
    <Card
        class="group relative cursor-pointer overflow-hidden border-border/60 bg-card transition-all duration-300 hover:border-border hover:shadow-lg hover:shadow-black/5 dark:border-border/40 dark:hover:shadow-black/20"
        :class="statusConfig.cardClass"
        @click="openRunDetail"
    >
        <!-- Active indicator animation -->
        <div
            v-if="isActive && isProcessing"
            class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 via-teal-500/5 to-emerald-500/5 dark:from-emerald-500/10 dark:via-teal-500/10 dark:to-emerald-500/10"
        />

        <!-- Animated border for processing -->
        <div
            v-if="run.status === 'processing'"
            class="absolute inset-x-0 top-0 h-0.5 overflow-hidden"
        >
            <div
                class="h-full w-full animate-pulse bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-500"
            />
        </div>

        <CardHeader class="pb-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <CardTitle
                        class="truncate text-base font-semibold text-foreground"
                    >
                        {{ configName }}
                    </CardTitle>
                    <CardDescription class="mt-1 flex items-center gap-2">
                        <span class="font-mono text-xs text-muted-foreground">
                            Run #{{ run.id }}
                        </span>
                    </CardDescription>
                </div>

                <Badge
                    variant="outline"
                    class="shrink-0 gap-1.5 text-xs font-medium"
                    :class="statusConfig.badgeClass"
                >
                    <component
                        :is="statusConfig.icon"
                        class="size-3.5"
                        :class="statusConfig.iconClass"
                    />
                    {{ statusConfig.label }}
                    <span
                        v-if="run.status === 'processing'"
                        class="tabular-nums"
                    >
                        {{ run.progress_percent }}%
                    </span>
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-4 pt-0">
            <!-- Source → Target -->
            <div v-if="sourceTarget" class="flex items-center gap-2 text-sm">
                <div
                    class="flex min-w-0 flex-1 items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                >
                    <Database
                        class="size-4 shrink-0 text-muted-foreground/70"
                    />
                    <span class="truncate font-medium text-foreground">
                        {{ sourceTarget.source }}
                    </span>
                </div>
                <ArrowRight class="size-4 shrink-0 text-muted-foreground" />
                <div
                    class="flex min-w-0 flex-1 items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                >
                    <Database
                        class="size-4 shrink-0 text-muted-foreground/70"
                    />
                    <span class="truncate font-medium text-foreground">
                        {{ sourceTarget.target }}
                    </span>
                </div>
            </div>

            <!-- Progress Bar (only for processing/queued) -->
            <div v-if="isProcessing" class="space-y-2">
                <div
                    class="flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>Progress</span>
                    <span class="tabular-nums">
                        {{ run.current_step }} / {{ run.total_steps }} tables
                    </span>
                </div>
                <div
                    class="h-2 w-full overflow-hidden rounded-full bg-muted/60 dark:bg-muted/40"
                >
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="
                            run.status === 'processing'
                                ? 'bg-gradient-to-r from-emerald-500 to-teal-500'
                                : 'bg-blue-500'
                        "
                        :style="{ width: `${run.progress_percent}%` }"
                    />
                </div>
            </div>

            <!-- Meta Info -->
            <div
                class="flex items-center justify-between text-xs text-muted-foreground"
            >
                <div class="flex items-center gap-1.5">
                    <Calendar class="size-3.5" />
                    <span>{{ formattedStartedAt }}</span>
                </div>
                <div v-if="duration" class="flex items-center gap-1.5">
                    <Timer class="size-3.5" />
                    <span class="tabular-nums">{{ duration }}</span>
                </div>
            </div>

            <!-- Error Message (if failed) -->
            <div
                v-if="run.status === 'failed' && run.error_message"
                class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950/30"
            >
                <div class="flex items-start gap-2">
                    <AlertCircle
                        class="mt-0.5 size-4 shrink-0 text-red-600 dark:text-red-400"
                    />
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ run.error_message }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
