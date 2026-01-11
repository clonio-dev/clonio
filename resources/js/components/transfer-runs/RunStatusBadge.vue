<script setup lang="ts">
import type { RunStatus, StatusBadgeProps } from '@/types/transfer-run.types';
import { computed } from 'vue';

const props = defineProps<StatusBadgeProps>();

const statusConfig = computed(() => {
    const configs: Record<
        RunStatus,
        { icon: string; label: string; class: string }
    > = {
        queued: {
            icon: '⏳',
            label: 'Queued',
            class: 'bg-blue-100 text-blue-800 border-blue-200',
        },
        processing: {
            icon: '✅',
            label: 'Running',
            class: 'bg-green-100 text-green-800 border-green-200 animate-pulse',
        },
        completed: {
            icon: '✓',
            label: 'Completed',
            class: 'bg-green-100 text-green-800 border-green-200',
        },
        failed: {
            icon: '❌',
            label: 'Failed',
            class: 'bg-red-100 text-red-800 border-red-200',
        },
        cancelled: {
            icon: '⏹',
            label: 'Cancelled',
            class: 'bg-gray-100 text-gray-800 border-gray-200',
        },
    };

    return configs[props.status];
});
</script>

<template>
    <div
        class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm font-medium"
        :class="statusConfig.class"
    >
        <span>{{ statusConfig.icon }}</span>
        <span>{{ statusConfig.label }}</span>
        <span
            v-if="progress !== undefined && status === 'processing'"
            class="text-xs opacity-75"
        >
            {{ progress }}%
        </span>
    </div>
</template>
