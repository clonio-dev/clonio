<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import type { RunLogProps, LogLevel } from '@/types/transfer-run.types';

const props = defineProps<RunLogProps>();

const autoScroll = ref(true);
const logContainer = ref<HTMLElement | null>(null);
let refreshInterval: number | null = null;

const sortedLogs = computed(() => {
    if (!props.initialLogs) return [];
    return [...props.initialLogs].sort((a, b) =>
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );
});

const logLevelConfig: Record<LogLevel, { icon: string; class: string }> = {
    info: {
        icon: 'ℹ️',
        class: 'text-blue-700 bg-blue-50 border-blue-200'
    },
    warning: {
        icon: '⚠️',
        class: 'text-yellow-700 bg-yellow-50 border-yellow-200'
    },
    error: {
        icon: '❌',
        class: 'text-red-700 bg-red-50 border-red-200'
    }
};

function formatTimestamp(timestamp: string): string {
    return new Date(timestamp).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function formatEventType(eventType: string): string {
    return eventType
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
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
        }
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
watch(() => props.initialLogs?.length, async () => {
    if (autoScroll.value) {
        await nextTick();
        scrollToBottom();
    }
});

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
    <div class="bg-white border rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">Transfer Log</h3>
                <span class="text-sm text-gray-600">{{ sortedLogs.length }} entries</span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="toggleAutoScroll"
                    class="px-3 py-1.5 text-sm rounded border transition-colors"
                    :class="autoScroll
            ? 'bg-green-50 border-green-200 text-green-700'
            : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'"
                >
                    {{ autoScroll ? '⬇ Auto-scroll ON' : '⬇ Auto-scroll OFF' }}
                </button>

                <button
                    @click="exportLogs"
                    class="px-3 py-1.5 text-sm rounded border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
                >
                    📥 Export
                </button>
            </div>
        </div>

        <!-- Log Container -->
        <div
            ref="logContainer"
            class="overflow-y-auto font-mono text-sm"
            style="max-height: 600px;"
        >
            <div v-if="sortedLogs.length === 0" class="p-8 text-center text-gray-500">
                <p>No logs yet...</p>
            </div>

            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="log in sortedLogs"
                    :key="log.id"
                    class="px-4 py-3 hover:bg-gray-50 transition-colors border-l-4"
                    :class="logLevelConfig[log.level].class"
                >
                    <!-- Log Header -->
                    <div class="flex items-start gap-3 mb-1">
                        <span class="flex-shrink-0">{{ logLevelConfig[log.level].icon }}</span>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-gray-500 font-sans">
                  {{ formatTimestamp(log.created_at) }}
                </span>
                                <span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-sans">
                  {{ formatEventType(log.event_type) }}
                </span>
                            </div>

                            <!-- Log Message -->
                            <p class="text-gray-900 font-sans">{{ log.message }}</p>

                            <!-- Log Data (if present) -->
                            <div v-if="Object.keys(log.data).length > 0" class="mt-2 text-xs text-gray-600">
                                <details class="cursor-pointer">
                                    <summary class="hover:text-gray-900">Details</summary>
                                    <pre class="mt-2 p-2 bg-gray-100 rounded overflow-x-auto">{{ JSON.stringify(log.data, null, 2) }}</pre>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
