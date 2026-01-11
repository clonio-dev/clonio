<script setup lang="ts">
import type { LogLevel, RunLogProps } from '@/types/transfer-run.types';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<RunLogProps>();

const autoScroll = ref(true);
const logContainer = ref<HTMLElement | null>(null);
let refreshInterval: number | null = null;

const sortedLogs = computed(() => {
    if (!props.initialLogs) return [];
    return [...props.initialLogs].sort(
        (a, b) =>
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
    );
});

const logLevelConfig: Record<LogLevel, { icon: string; class: string }> = {
    info: {
        icon: 'ℹ️',
        class: 'text-blue-700 bg-blue-50 border-blue-200',
    },
    warning: {
        icon: '⚠️',
        class: 'text-yellow-700 bg-yellow-50 border-yellow-200',
    },
    error: {
        icon: '❌',
        class: 'text-red-700 bg-red-50 border-red-200',
    },
};

function formatTimestamp(timestamp: string): string {
    return new Date(timestamp).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function formatEventType(eventType: string): string {
    return eventType
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function refreshLogs() {
    router.reload({
        only: ['run.logs'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
            if (autoScroll.value) {
                await nextTick();
                scrollToBottom();
            }
        },
    });
}

function scrollToBottom() {
    if (logContainer.value) {
        logContainer.value.scrollTop = logContainer.value.scrollHeight;
    }
}

function toggleAutoScroll() {
    autoScroll.value = !autoScroll.value;
    if (autoScroll.value) {
        scrollToBottom();
    }
}

function exportLogs() {
    router.visit(`/transfer-runs/${props.runId}/logs/export`, {
        method: 'get',
        preserveState: true,
    });
}

// Watch for log changes to auto-scroll
watch(
    () => props.initialLogs?.length,
    async () => {
        if (autoScroll.value) {
            await nextTick();
            scrollToBottom();
        }
    },
);

onMounted(() => {
    refreshInterval = window.setInterval(refreshLogs, 2000); // Every 2s
    scrollToBottom();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-white">
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b bg-gray-50 px-4 py-3"
        >
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">Transfer Log</h3>
                <span class="text-sm text-gray-600"
                    >{{ sortedLogs.length }} entries</span
                >
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="toggleAutoScroll"
                    class="rounded border px-3 py-1.5 text-sm transition-colors"
                    :class="
                        autoScroll
                            ? 'border-green-200 bg-green-50 text-green-700'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'
                    "
                >
                    {{ autoScroll ? '⬇ Auto-scroll ON' : '⬇ Auto-scroll OFF' }}
                </button>

                <button
                    @click="exportLogs"
                    class="rounded border border-gray-200 px-3 py-1.5 text-sm text-gray-600 transition-colors hover:bg-gray-50"
                >
                    📥 Export
                </button>
            </div>
        </div>

        <!-- Log Container -->
        <div
            ref="logContainer"
            class="overflow-y-auto font-mono text-sm"
            style="max-height: 600px"
        >
            <div
                v-if="sortedLogs.length === 0"
                class="p-8 text-center text-gray-500"
            >
                <p>No logs yet...</p>
            </div>

            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="log in sortedLogs"
                    :key="log.id"
                    class="border-l-4 px-4 py-3 transition-colors hover:bg-gray-50"
                    :class="logLevelConfig[log.level].class"
                >
                    <!-- Log Header -->
                    <div class="mb-1 flex items-start gap-3">
                        <span class="flex-shrink-0">{{
                            logLevelConfig[log.level].icon
                        }}</span>

                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="font-sans text-xs text-gray-500">
                                    {{ formatTimestamp(log.created_at) }}
                                </span>
                                <span
                                    class="rounded bg-gray-100 px-2 py-0.5 font-sans text-xs"
                                >
                                    {{ formatEventType(log.event_type) }}
                                </span>
                            </div>

                            <!-- Log Message -->
                            <p class="font-sans text-gray-900">
                                {{ log.message }}
                            </p>

                            <!-- Log Data (if present) -->
                            <div
                                v-if="Object.keys(log.data).length > 0"
                                class="mt-2 text-xs text-gray-600"
                            >
                                <details class="cursor-pointer">
                                    <summary class="hover:text-gray-900">
                                        Details
                                    </summary>
                                    <pre
                                        class="mt-2 overflow-x-auto rounded bg-gray-100 p-2"
                                        >{{
                                            JSON.stringify(log.data, null, 2)
                                        }}</pre
                                    >
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
