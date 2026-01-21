<script setup lang="ts">
import type { LogLevel, TransferRunLog } from '@/types/transfer-run.types';
import {
    Info,
    Loader2,
    Terminal,
    TriangleAlert,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    logs: TransferRunLog[];
    runId: number;
    isActive: boolean;
    finishedAt: string | null;
}

const props = defineProps<Props>();

const logContainerRef = ref<HTMLDivElement | null>(null);
const autoScroll = ref(true);
const activeFilters = ref<Set<LogLevel>>(new Set());

const logLevelConfig: Record<
    LogLevel,
    {
        icon: typeof Info;
        activeClass: string;
        inactiveClass: string;
        textClass: string;
        labelClass: string;
    }
> = {
    info: {
        icon: Info,
        activeClass: 'bg-sky-500/20 text-sky-400 ring-1 ring-sky-500/50',
        inactiveClass: 'text-sky-400/60 hover:text-sky-400 hover:bg-sky-500/10',
        textClass: 'text-sky-400',
        labelClass: 'text-sky-500',
    },
    warning: {
        icon: TriangleAlert,
        activeClass: 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/50',
        inactiveClass:
            'text-amber-400/60 hover:text-amber-400 hover:bg-amber-500/10',
        textClass: 'text-amber-400',
        labelClass: 'text-amber-500',
    },
    error: {
        icon: XCircle,
        activeClass: 'bg-red-500/20 text-red-400 ring-1 ring-red-500/50',
        inactiveClass: 'text-red-400/60 hover:text-red-400 hover:bg-red-500/10',
        textClass: 'text-red-400',
        labelClass: 'text-red-500',
    },
};

const logStats = computed(() => {
    const stats: Record<LogLevel, number> = { info: 0, warning: 0, error: 0 };
    props.logs.forEach((log) => {
        if (log.level in stats) {
            stats[log.level]++;
        }
    });
    return stats;
});

const hasActiveFilters = computed(() => activeFilters.value.size > 0);

const filteredLogs = computed(() => {
    if (!hasActiveFilters.value) {
        return props.logs;
    }
    return props.logs.filter((log) => activeFilters.value.has(log.level));
});

const formattedFinishedAt = computed(() => {
    if (!props.finishedAt) return null;
    return new Date(props.finishedAt).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

function toggleFilter(level: LogLevel) {
    if (activeFilters.value.has(level)) {
        activeFilters.value.delete(level);
    } else {
        activeFilters.value.add(level);
    }
    // Trigger reactivity
    activeFilters.value = new Set(activeFilters.value);
}

function isFilterActive(level: LogLevel): boolean {
    return activeFilters.value.has(level);
}

function clearFilters() {
    activeFilters.value = new Set();
}

function isLogHighlighted(log: TransferRunLog): boolean {
    if (!hasActiveFilters.value) return true;
    return activeFilters.value.has(log.level);
}

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

function scrollToBottom() {
    nextTick(() => {
        if (logContainerRef.value) {
            logContainerRef.value.scrollTop =
                logContainerRef.value.scrollHeight;
        }
    });
}

function handleScroll() {
    if (!logContainerRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = logContainerRef.value;
    autoScroll.value = scrollHeight - scrollTop - clientHeight < 100;
}

function resumeAutoScroll() {
    autoScroll.value = true;
    scrollToBottom();
}

watch(
    () => props.logs.length,
    () => {
        if (autoScroll.value) {
            scrollToBottom();
        }
    },
);

onMounted(() => {
    scrollToBottom();
});

defineExpose({
    scrollToBottom,
});
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-border/60 dark:border-border/40"
    >
        <!-- Console Header -->
        <div
            class="flex items-center justify-between border-b border-zinc-800 bg-zinc-900 px-4 py-3"
        >
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="size-3 rounded-full bg-red-500/80"></div>
                    <div class="size-3 rounded-full bg-yellow-500/80"></div>
                    <div class="size-3 rounded-full bg-green-500/80"></div>
                </div>
                <span class="font-mono text-sm text-zinc-400">
                    transfer-run-{{ runId }}.log
                </span>
            </div>

            <!-- Level Filter Buttons -->
            <div class="flex items-center gap-2">
                <!-- Clear filters button -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="ml-1 rounded-md px-2 py-1 text-xs text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300"
                    title="Clear all filters"
                >
                    Clear
                </button>

                <button
                    v-for="level in ['info', 'warning', 'error'] as LogLevel[]"
                    :key="level"
                    @click="toggleFilter(level)"
                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium transition-all duration-150"
                    :class="
                        isFilterActive(level)
                            ? logLevelConfig[level].activeClass
                            : logLevelConfig[level].inactiveClass
                    "
                    :title="`${isFilterActive(level) ? 'Hide' : 'Show only'} ${level} logs`"
                >
                    <component
                        :is="logLevelConfig[level].icon"
                        class="size-3.5"
                    />
                    <span class="tabular-nums">{{ logStats[level] }}</span>
                </button>
            </div>
        </div>

        <!-- Active Filters Indicator -->
        <div
            v-if="hasActiveFilters"
            class="flex items-center gap-2 border-b border-zinc-800 bg-zinc-900/50 px-4 py-2 text-xs"
        >
            <span class="text-zinc-900 dark:text-zinc-500">Filtering:</span>
            <div class="flex items-center gap-1.5">
                <span
                    v-for="level in activeFilters"
                    :key="level"
                    class="rounded px-1.5 py-0.5 font-medium uppercase"
                    :class="logLevelConfig[level].activeClass"
                >
                    {{ level }}
                </span>
            </div>
            <span class="text-zinc-800 dark:text-zinc-400">
                ({{ filteredLogs.length }} of {{ logs.length }} entries)
            </span>
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

            <!-- No Results After Filter -->
            <div
                v-else-if="filteredLogs.length === 0 && hasActiveFilters"
                class="flex h-full items-center justify-center text-zinc-500"
            >
                <div class="text-center">
                    <Terminal class="mx-auto mb-3 size-10 opacity-50" />
                    <p>No logs match the selected filters</p>
                    <button
                        @click="clearFilters"
                        class="mt-3 rounded-md bg-zinc-800 px-3 py-1.5 text-sm text-zinc-300 transition-colors hover:bg-zinc-700"
                    >
                        Clear filters
                    </button>
                </div>
            </div>

            <!-- Log Entries -->
            <div v-else class="space-y-0.5">
                <div
                    v-for="log in filteredLogs"
                    :key="log.id"
                    class="group rounded px-2 py-1 transition-all duration-150"
                    :class="
                        isLogHighlighted(log)
                            ? 'hover:bg-zinc-900'
                            : 'opacity-30 hover:opacity-60'
                    "
                >
                    <!-- Main log line -->
                    <div class="flex items-start gap-3">
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
                        <span class="break-words text-zinc-300">
                            {{ log.message }}
                        </span>
                    </div>

                    <!-- Data (if present and has content) - on new line -->
                    <div
                        v-if="log.data && Object.keys(log.data).length > 0"
                        class="flex"
                    >
                        <!-- Spacer to align with content after level -->
                        <span class="shrink-0 text-transparent">
                            [{{ formatLogTime(log.created_at) }}]
                        </span>
                        <span class="w-16 shrink-0"></span>
                        <span class="ml-6 break-all text-zinc-500">
                            {{ JSON.stringify(log.data) }}
                        </span>
                    </div>
                </div>

                <!-- Cursor / Active Indicator -->
                <div v-if="isActive" class="flex items-center gap-2 px-2 py-1">
                    <span class="animate-pulse text-emerald-400">_</span>
                </div>
            </div>
        </div>

        <!-- Console Footer -->
        <div
            class="flex items-center justify-between border-t border-zinc-800 bg-zinc-900 px-4 py-2 text-xs text-zinc-500"
        >
            <span>
                {{
                    hasActiveFilters
                        ? `${filteredLogs.length} / ${logs.length}`
                        : logs.length
                }}
                log entries
            </span>
            <div class="flex items-center gap-4">
                <button
                    v-if="!autoScroll && isActive"
                    @click="resumeAutoScroll"
                    class="text-emerald-400 hover:text-emerald-300"
                >
                    Resume auto-scroll
                </button>
                <span v-if="finishedAt">
                    Completed: {{ formattedFinishedAt }}
                </span>
                <span v-else-if="isActive" class="flex items-center gap-1.5">
                    <Loader2 class="size-3 animate-spin" />
                    Processing...
                </span>
            </div>
        </div>
    </div>
</template>
