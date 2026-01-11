<script setup lang="ts">
import type { RunDetailProps } from '@/types/transfer-run.types';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import RunLog from './RunLog.vue';
import RunStatusBadge from './RunStatusBadge.vue';

const props = defineProps<RunDetailProps>();

let refreshInterval: number | null = null;

const run = computed(() => props.initialRun);

const isActive = computed(() => {
    return run.value && ['queued', 'processing'].includes(run.value.status);
});

const configName = computed(() => {
    if (!run.value) return 'Loading...';
    return (
        run.value.config?.name ||
        run.value.config_snapshot?.name ||
        'Unknown Config'
    );
});

const sourceTarget = computed(() => {
    if (!run.value?.config_snapshot) return null;

    const snapshot = run.value.config_snapshot;
    return {
        source: `${snapshot.source_connection.name} (${snapshot.source_connection.type})`,
        target: `${snapshot.target_connection.name} (${snapshot.target_connection.type})`,
    };
});

const duration = computed(() => {
    if (!run.value?.started_at) return null;

    const start = new Date(run.value.started_at);
    const end = run.value.finished_at
        ? new Date(run.value.finished_at)
        : new Date();

    const diffMs = end.getTime() - start.getTime();
    const hours = Math.floor(diffMs / 3600000);
    const mins = Math.floor((diffMs % 3600000) / 60000);
    const secs = Math.floor((diffMs % 60000) / 1000);

    if (hours > 0) return `${hours}h ${mins}m ${secs}s`;
    if (mins > 0) return `${mins}m ${secs}s`;
    return `${secs}s`;
});

const estimatedRemaining = computed(() => {
    if (!run.value || run.value.progress_percent === 0) return null;
    if (!isActive.value) return null;

    const start = new Date(run.value.started_at!);
    const now = new Date();
    const elapsed = now.getTime() - start.getTime();

    const progressDecimal = run.value.progress_percent / 100;
    const total = elapsed / progressDecimal;
    const remaining = total - elapsed;

    const mins = Math.floor(remaining / 60000);
    const secs = Math.floor((remaining % 60000) / 1000);

    if (mins > 0) return `~${mins}m ${secs}s`;
    return `~${secs}s`;
});

function refreshRunData() {
    router.reload({
        only: ['initialRun'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // Stop polling if run finished
            if (
                run.value &&
                !['queued', 'processing'].includes(run.value.status)
            ) {
                stopRefresh();
            }
        },
    });
}

function cancelRun() {
    if (!confirm('Are you sure you want to cancel this transfer?')) {
        return;
    }

    router.post(
        `/transfer-runs/${props.runId}/cancel`,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                stopRefresh();
            },
        },
    );
}

function retryRun() {
    if (!confirm('Retry this transfer? This will create a new run.')) {
        return;
    }

    router.post(`/transfer-runs/${props.runId}/retry`);
}

function stopRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

function goBack() {
    router.visit('/dashboard');
}

onMounted(() => {
    if (isActive.value) {
        refreshInterval = window.setInterval(refreshRunData, 2000); // Every 2s
    }
});

onUnmounted(() => {
    stopRefresh();
});
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <div class="mb-6">
            <button
                @click="goBack"
                class="mb-4 inline-flex items-center gap-2 font-medium text-blue-600 hover:text-blue-700"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Back to Dashboard
            </button>

            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ configName }}
                    </h1>
                    <p class="mt-1 text-gray-600">Transfer Run #{{ runId }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="isActive"
                        @click="cancelRun"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 font-medium text-white transition-colors hover:bg-red-700"
                    >
                        🛑 Cancel
                    </button>

                    <button
                        v-if="run?.status === 'failed'"
                        @click="retryRun"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        🔄 Retry
                    </button>
                </div>
            </div>
        </div>

        <!-- Status Section -->
        <div class="mb-6 rounded-lg border bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Status</h2>
                <RunStatusBadge
                    v-if="run"
                    :status="run.status"
                    :progress="run.progress_percent"
                />
            </div>

            <!-- Source → Target -->
            <div v-if="sourceTarget" class="mb-4 border-b pb-4">
                <div class="flex items-center gap-3 text-sm">
                    <div class="flex-1">
                        <p class="mb-1 text-gray-500">Source</p>
                        <p class="font-medium">{{ sourceTarget.source }}</p>
                    </div>
                    <svg
                        class="h-6 w-6 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>
                    <div class="flex-1">
                        <p class="mb-1 text-gray-500">Target</p>
                        <p class="font-medium">{{ sourceTarget.target }}</p>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div
                v-if="run && ['processing', 'queued'].includes(run.status)"
                class="mb-4"
            >
                <div
                    class="mb-2 flex items-center justify-between text-sm text-gray-600"
                >
                    <span>Overall Progress</span>
                    <span
                        >{{ run.current_step }} /
                        {{ run.total_steps }} tables</span
                    >
                </div>
                <div class="h-3 w-full rounded-full bg-gray-200">
                    <div
                        class="flex h-3 items-center justify-end rounded-full bg-green-500 pr-2 transition-all duration-500"
                        :style="{ width: `${run.progress_percent}%` }"
                    >
                        <span
                            v-if="run.progress_percent > 10"
                            class="text-xs font-medium text-white"
                        >
                            {{ run.progress_percent }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Meta Info Grid -->
            <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                <div>
                    <p class="mb-1 text-gray-500">Started</p>
                    <p class="font-medium">
                        {{
                            run?.started_at
                                ? new Date(run.started_at).toLocaleString(
                                      'de-DE',
                                  )
                                : '-'
                        }}
                    </p>
                </div>

                <div v-if="run?.finished_at">
                    <p class="mb-1 text-gray-500">Finished</p>
                    <p class="font-medium">
                        {{ new Date(run.finished_at).toLocaleString('de-DE') }}
                    </p>
                </div>

                <div>
                    <p class="mb-1 text-gray-500">Duration</p>
                    <p class="font-medium">{{ duration || '-' }}</p>
                </div>

                <div v-if="estimatedRemaining">
                    <p class="mb-1 text-gray-500">Estimated Remaining</p>
                    <p class="font-medium">{{ estimatedRemaining }}</p>
                </div>
            </div>

            <!-- Error Message -->
            <div
                v-if="run?.status === 'failed' && run.error_message"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4"
            >
                <p class="mb-1 text-sm font-medium text-red-900">Error</p>
                <p class="text-sm text-red-800">{{ run.error_message }}</p>
            </div>

            <!-- Auto-update indicator -->
            <div
                v-if="isActive"
                class="mt-4 flex items-center gap-2 text-sm text-gray-600"
            >
                <div
                    class="h-2 w-2 animate-pulse rounded-full bg-green-500"
                ></div>
                <span>Auto-updating every 2 seconds</span>
            </div>
        </div>

        <!-- Log Section -->
        <RunLog :run-id="runId" :initial-logs="run?.logs" />
    </div>
</template>
