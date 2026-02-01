<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { CloningRun, CloningRunStatus } from '@/types/cloning.types';
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    CheckCircle2,
    Clock,
    Database,
    Loader2,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    run: CloningRun;
    isActive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isActive: false,
});

const statusConfig: Record<
    CloningRunStatus,
    {
        icon: typeof CheckCircle2;
        label: string;
        badgeClass: string;
        iconClass: string;
    }
> = {
    queued: {
        icon: Clock,
        label: 'Queued',
        badgeClass:
            'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900',
        iconClass: 'text-blue-600 dark:text-blue-400',
    },
    processing: {
        icon: Loader2,
        label: 'Running',
        badgeClass:
            'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-900',
        iconClass: 'text-emerald-600 dark:text-emerald-400 animate-spin',
    },
    completed: {
        icon: CheckCircle2,
        label: 'Completed',
        badgeClass:
            'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/50 dark:text-green-400 dark:border-green-900',
        iconClass: 'text-green-600 dark:text-green-400',
    },
    failed: {
        icon: XCircle,
        label: 'Failed',
        badgeClass:
            'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-900',
        iconClass: 'text-red-600 dark:text-red-400',
    },
    cancelled: {
        icon: AlertCircle,
        label: 'Cancelled',
        badgeClass:
            'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-950/50 dark:text-gray-400 dark:border-gray-900',
        iconClass: 'text-gray-600 dark:text-gray-400',
    },
};

const currentStatus = computed(
    () => statusConfig[props.run.status] || statusConfig.cancelled,
);

const formattedDate = computed(() => {
    const date = props.run.started_at || props.run.created_at;
    if (!date) return '';
    return new Date(date).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const duration = computed(() => {
    if (!props.run.started_at) return '-';
    const start = new Date(props.run.started_at);
    const end = props.run.finished_at
        ? new Date(props.run.finished_at)
        : new Date();
    const diffMs = end.getTime() - start.getTime();
    const mins = Math.floor(diffMs / 60000);
    const secs = Math.floor((diffMs % 60000) / 1000);
    if (mins > 0) {
        return `${mins}m ${secs}s`;
    }
    return `${secs}s`;
});
</script>

<template>
    <Link :href="`/cloning-runs/${run.id}`">
        <Card
            class="group cursor-pointer border-border/60 transition-all hover:border-border hover:shadow-md dark:border-border/40 dark:hover:border-border/60 h-full"
            :class="{ 'ring-2 ring-emerald-500/20': isActive }"
        >
            <CardContent class="p-4 py-0">
                <div class="mb-3 flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-muted/50"
                        >
                            <Database class="size-4 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ run.cloning?.title || `Run #${run.id}` }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formattedDate }}
                            </p>
                        </div>
                    </div>
                    <Badge
                        variant="outline"
                        class="gap-1.5 text-xs"
                        :class="currentStatus.badgeClass"
                    >
                        <component
                            :is="currentStatus.icon"
                            class="size-3"
                            :class="currentStatus.iconClass"
                        />
                        {{ currentStatus.label }}
                    </Badge>
                </div>

                <!-- Progress (for active runs) -->
                <div v-if="run.status === 'processing'">
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Progress</span>
                        <span class="font-medium text-foreground tabular-nums">
                            {{ run.progress_percent }}%
                        </span>
                    </div>
                    <div
                        class="h-1.5 w-full overflow-hidden rounded-full bg-muted/60"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                            :style="{ width: `${run.progress_percent}%` }"
                        />
                    </div>
                </div>

                <!-- Info Row -->
                <div
                    class="flex items-center justify-between text-xs text-muted-foreground"
                >
                    <div class="flex items-center gap-4">
                        <span
                            v-if="run.cloning?.source_connection"
                            class="flex items-center gap-1"
                        >
                            <Database class="size-3" />
                            {{ run.cloning.source_connection.name }}
                        </span>
                        <ArrowRight
                            v-if="run.cloning?.target_connection"
                            class="size-3"
                        />
                        <span v-if="run.cloning?.target_connection">
                            {{ run.cloning.target_connection.name }}
                        </span>
                    </div>
                    <span class="tabular-nums">{{ duration }}</span>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
