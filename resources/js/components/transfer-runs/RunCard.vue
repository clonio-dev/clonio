<script setup lang="ts">
import type { RunCardProps } from '@/types/transfer-run.types';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import RunStatusBadge from './RunStatusBadge.vue';

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
    return (
        props.run.config?.name ||
        props.run.config_snapshot?.name ||
        'Unknown Config'
    );
});

const sourceTarget = computed(() => {
    const snapshot = props.run.config_snapshot;
    if (!snapshot) return null;

    return {
        source: `${snapshot.source_connection.name} (${snapshot.source_connection.type})`,
        target: `${snapshot.target_connection.name} (${snapshot.target_connection.type})`,
    };
});

function openRunDetail() {
    router.visit(`/transfer-runs/${props.run.id}`);
}
</script>

<template>
    <div
        class="cursor-pointer rounded-lg border bg-white p-4 transition-shadow hover:shadow-lg"
        :class="{ 'ring-opacity-50 ring-2 ring-green-500': isActive }"
        @click="openRunDetail"
    >
        <!-- Header -->
        <div class="mb-3 flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold">{{ configName }}</h3>
                <p class="text-sm text-gray-500">Run #{{ run.id }}</p>
            </div>
            <RunStatusBadge
                :status="run.status"
                :progress="run.progress_percent"
            />
        </div>

        <!-- Source → Target -->
        <div v-if="sourceTarget" class="mb-3 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <span class="font-medium">{{ sourceTarget.source }}</span>
                <span>→</span>
                <span class="font-medium">{{ sourceTarget.target }}</span>
            </div>
        </div>

        <!-- Progress Bar (only for processing/queued) -->
        <div v-if="['processing', 'queued'].includes(run.status)" class="mb-3">
            <div
                class="mb-1 flex items-center justify-between text-xs text-gray-600"
            >
                <span>Progress</span>
                <span
                    >{{ run.current_step }} / {{ run.total_steps }} tables</span
                >
            </div>
            <div class="h-2 w-full rounded-full bg-gray-200">
                <div
                    class="h-2 rounded-full bg-green-500 transition-all duration-500"
                    :style="{ width: `${run.progress_percent}%` }"
                ></div>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ formattedStartedAt }}</span>
            <span v-if="duration">{{ duration }}</span>
        </div>

        <!-- Error Message (if failed) -->
        <div
            v-if="run.status === 'failed' && run.error_message"
            class="mt-3 rounded border border-red-200 bg-red-50 p-2 text-sm text-red-800"
        >
            <span class="font-medium">Error:</span> {{ run.error_message }}
        </div>
    </div>
</template>
