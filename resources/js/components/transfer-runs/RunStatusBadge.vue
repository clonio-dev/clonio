<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { RunStatus, StatusBadgeProps } from '@/types/transfer-run.types';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    Loader2,
    XCircle,
} from 'lucide-vue-next';
import { computed, type Component } from 'vue';

const props = defineProps<StatusBadgeProps>();

interface StatusConfig {
    icon: Component;
    label: string;
    badgeClass: string;
    iconClass: string;
}

const statusConfig = computed<StatusConfig>(() => {
    const configs: Record<RunStatus, StatusConfig> = {
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

    return configs[props.status];
});
</script>

<template>
    <Badge
        variant="outline"
        class="gap-1.5 text-xs font-medium"
        :class="statusConfig.badgeClass"
    >
        <component
            :is="statusConfig.icon"
            class="size-3.5"
            :class="statusConfig.iconClass"
        />
        <span>{{ statusConfig.label }}</span>
        <span
            v-if="progress !== undefined && status === 'processing'"
            class="tabular-nums opacity-80"
        >
            {{ progress }}%
        </span>
    </Badge>
</template>
