<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Progress } from '@/components/ui/progress';
import type {
    CloningRunLog,
    TableTransferProgressData,
} from '@/types/cloning.types';
import {
    ChevronDownIcon,
    ChevronRightIcon,
    CircleCheckBigIcon,
    Info,
    LogsIcon,
    Terminal,
    TriangleAlertIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    logs: CloningRunLog[];
    runId: number;
    isActive: boolean;
    finishedAt: string | null;
    lineLength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    lineLength: 80,
});

const consoleRef = ref<HTMLDivElement | null>(null);
const autoScroll = ref(true);
const verbose = ref(false);

const levelConfig: Record<string, { icon: typeof Info; class: string }> = {
    info: {
        icon: Info,
        class: 'text-blue-600 dark:text-blue-400',
    },
    success: {
        icon: CircleCheckBigIcon,
        class: 'text-green-600 dark:text-green-400',
    },
    warning: {
        icon: TriangleAlertIcon,
        class: 'text-amber-600 dark:text-amber-400',
    },
    error: {
        icon: XIcon,
        class: 'text-red-600 dark:text-red-400',
    },
    debug: {
        icon: LogsIcon,
        class: 'text-slate-400 text-slate-400',
    },
};

function isProgressLog(log: CloningRunLog): boolean {
    return log.event_type === 'table_transfer_progress';
}

function getProgressData(log: CloningRunLog): TableTransferProgressData | null {
    if (!isProgressLog(log)) return null;
    return log.data as TableTransferProgressData;
}

interface DisplayLog {
    log: CloningRunLog;
    isCollapsed: boolean;
    collapsedCount: number;
    collapsedLogs: CloningRunLog[];
}

const sortedLogs = computed(() => {
    return [...props.logs]
        .filter(
            ({ level }: CloningRunLog) =>
                verbose.value || !['debug'].includes(level),
        )
        .sort(
            (a: CloningRunLog, b: CloningRunLog) =>
                new Date(a.created_at).getTime() -
                new Date(b.created_at).getTime(),
        );
});

const displayLogs = computed((): DisplayLog[] => {
    const logs = sortedLogs.value;
    const result: DisplayLog[] = [];

    // Track the latest progress log per table
    const progressByTable = new Map<
        string,
        { index: number; logs: CloningRunLog[] }
    >();

    for (let i = 0; i < logs.length; i++) {
        const log = logs[i];

        if (isProgressLog(log)) {
            const tableName = (log.data as TableTransferProgressData).table;

            if (progressByTable.has(tableName)) {
                // Add to existing group
                progressByTable.get(tableName)!.logs.push(log);
            } else {
                // Start new group, add placeholder to result
                progressByTable.set(tableName, {
                    index: result.length,
                    logs: [log],
                });
                result.push({
                    log,
                    isCollapsed: true,
                    collapsedCount: 0,
                    collapsedLogs: [],
                });
            }
        } else {
            result.push({
                log,
                isCollapsed: false,
                collapsedCount: 0,
                collapsedLogs: [],
            });
        }
    }

    // Update the progress entries with latest log and collapsed count
    for (const [, group] of progressByTable) {
        const latestLog = group.logs[group.logs.length - 1];
        result[group.index] = {
            log: latestLog,
            isCollapsed: true,
            collapsedCount: group.logs.length - 1,
            collapsedLogs: group.logs.slice(0, -1),
        };
    }

    return result;
});

const openedLog = ref<{ [key: number]: boolean }>({});
const expandedProgressTables = ref<Set<string>>(new Set());

function toggleProgressExpand(tableName: string): void {
    if (expandedProgressTables.value.has(tableName)) {
        expandedProgressTables.value.delete(tableName);
    } else {
        expandedProgressTables.value.add(tableName);
    }
}

function formatDuration(seconds: number | null | undefined): string | null {
    if (seconds === null || seconds === undefined || seconds <= 0) return null;

    if (seconds < 60) {
        return `~${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (minutes < 60) {
        return remainingSeconds > 0
            ? `~${minutes}m ${remainingSeconds}s`
            : `~${minutes}m`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return remainingMinutes > 0
        ? `~${hours}h ${remainingMinutes}m`
        : `~${hours}h`;
}

function formatSpeed(rowsPerSecond: number | undefined): string | null {
    if (!rowsPerSecond || rowsPerSecond <= 0) return null;

    if (rowsPerSecond >= 1000) {
        return `${(rowsPerSecond / 1000).toFixed(1)}k/s`;
    }

    return `${rowsPerSecond}/s`;
}

function formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function scrollToBottom() {
    if (autoScroll.value && consoleRef.value) {
        consoleRef.value.scrollTop = consoleRef.value.scrollHeight;
    }
}

function handleScroll() {
    if (!consoleRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = consoleRef.value;
    autoScroll.value = scrollTop + clientHeight >= scrollHeight - 50;
}

watch(
    () => props.logs.length,
    () => {
        nextTick(scrollToBottom);
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
        class="rounded-xl border border-border/60 bg-card dark:border-border/40"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-border/60 px-4 py-3 dark:border-border/40"
        >
            <div class="flex items-center gap-2">
                <Terminal class="size-4 text-muted-foreground" />
                <span class="font-medium text-foreground">Execution Log</span>
                <Badge variant="secondary" class="text-xs">
                    {{ logs.length }} entries
                    <template v-if="logs.length !== sortedLogs.length">
                        ({{ sortedLogs.length }} filtered)
                    </template>
                </Badge>
            </div>
            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <Checkbox v-model="verbose" id="verbose_output" />
                <label for="verbose_output" class="text-muted-foreground"
                    >Include verbose messages</label
                >
            </div>
            <div
                v-if="isActive"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <div class="size-2 animate-pulse rounded-full bg-emerald-500" />
                <span>Live</span>
            </div>
        </div>

        <!-- Console -->
        <div
            ref="consoleRef"
            class="h-96 overflow-y-auto bg-slate-950 p-4 font-mono text-sm dark:bg-black/50"
            @scroll="handleScroll"
        >
            <div
                v-if="sortedLogs.length === 0"
                class="flex h-full items-center justify-center"
            >
                <p class="text-slate-500">No log entries yet...</p>
            </div>

            <template v-for="displayLog in displayLogs" :key="displayLog.log.id">
                <!-- Progress log with collapsed history -->
                <div
                    v-if="isProgressLog(displayLog.log)"
                    class="mb-2"
                >
                    <!-- Collapsed history (when expanded) -->
                    <template
                        v-if="
                            displayLog.collapsedCount > 0 &&
                            expandedProgressTables.has(
                                (displayLog.log.data as TableTransferProgressData).table,
                            )
                        "
                    >
                        <div
                            v-for="collapsedLog in displayLog.collapsedLogs"
                            :key="collapsedLog.id"
                            class="group mb-1 flex gap-3 opacity-60"
                        >
                            <span class="shrink-0 text-slate-500 tabular-nums">
                                {{ formatTime(collapsedLog.created_at) }}
                            </span>
                            <component
                                :is="levelConfig[collapsedLog.level]?.icon || Info"
                                class="mt-0.5 size-4 shrink-0"
                                :class="
                                    levelConfig[collapsedLog.level]?.class ||
                                    'text-slate-400'
                                "
                            />
                            <div class="flex flex-1 items-center gap-2">
                                <span class="text-cyan-400/70">
                                    {{ collapsedLog.message }}
                                </span>
                                <Progress
                                    :model-value="
                                        getProgressData(collapsedLog)?.percent ?? 0
                                    "
                                    class="h-1.5 w-16 bg-slate-700"
                                />
                            </div>
                        </div>
                    </template>

                    <!-- Latest progress log -->
                    <div class="group flex gap-3 rounded bg-slate-900/50 px-1 py-0.5">
                        <span class="shrink-0 text-slate-500 tabular-nums">
                            {{ formatTime(displayLog.log.created_at) }}
                        </span>
                        <component
                            :is="levelConfig[displayLog.log.level]?.icon || Info"
                            class="mt-0.5 size-4 shrink-0"
                            :class="
                                levelConfig[displayLog.log.level]?.class ||
                                'text-slate-400'
                            "
                        />
                        <div class="flex flex-1 items-center gap-2">
                            <!-- Expand toggle if there's history -->
                            <button
                                v-if="displayLog.collapsedCount > 0"
                                @click="
                                    toggleProgressExpand(
                                        (displayLog.log.data as TableTransferProgressData).table,
                                    )
                                "
                                class="shrink-0 text-slate-500 hover:text-slate-300"
                            >
                                <ChevronDownIcon
                                    v-if="
                                        expandedProgressTables.has(
                                            (displayLog.log.data as TableTransferProgressData).table,
                                        )
                                    "
                                    class="size-4"
                                />
                                <ChevronRightIcon v-else class="size-4" />
                            </button>
                            <span class="text-cyan-400">
                                {{ displayLog.log.message }}
                            </span>
                            <Progress
                                :model-value="
                                    getProgressData(displayLog.log)?.percent ?? 0
                                "
                                class="h-1.5 w-16 bg-slate-700"
                            />
                            <Badge
                                variant="outline"
                                class="border-cyan-800 text-xs text-cyan-500"
                            >
                                {{ getProgressData(displayLog.log)?.percent }}%
                            </Badge>
                            <span
                                v-if="
                                    formatDuration(
                                        getProgressData(displayLog.log)
                                            ?.estimated_seconds_remaining,
                                    )
                                "
                                class="text-xs text-slate-400"
                            >
                                {{
                                    formatDuration(
                                        getProgressData(displayLog.log)
                                            ?.estimated_seconds_remaining,
                                    )
                                }}
                                remaining
                            </span>
                            <span
                                v-if="
                                    formatSpeed(
                                        getProgressData(displayLog.log)
                                            ?.rows_per_second,
                                    )
                                "
                                class="text-xs text-slate-500"
                            >
                                ({{
                                    formatSpeed(
                                        getProgressData(displayLog.log)
                                            ?.rows_per_second,
                                    )
                                }})
                            </span>
                            <Badge
                                v-if="displayLog.log.data.table"
                                variant="outline"
                                class="border-slate-700 text-xs text-slate-400"
                            >
                                {{ displayLog.log.data.table }}
                            </Badge>
                            <span
                                v-if="displayLog.collapsedCount > 0"
                                class="text-xs text-slate-500"
                            >
                                ({{ displayLog.collapsedCount }} previous)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Regular log entry -->
                <div
                    v-else
                    class="group mb-2 flex gap-3"
                >
                    <!-- Timestamp -->
                    <span class="shrink-0 text-slate-500 tabular-nums">
                        {{ formatTime(displayLog.log.created_at) }}
                    </span>

                    <!-- Level indicator -->
                    <component
                        :is="levelConfig[displayLog.log.level]?.icon || Info"
                        class="mt-0.5 size-4 shrink-0"
                        :class="
                            levelConfig[displayLog.log.level]?.class ||
                            'text-slate-400'
                        "
                    />

                    <!-- Message -->
                    <div class="flex-1">
                        <span
                            class="text-slate-200"
                            :class="{
                                'text-red-400': displayLog.log.level === 'error',
                                'text-amber-500': displayLog.log.level === 'warning',
                                'text-blue-400': displayLog.log.level === 'info',
                                'text-green-400': displayLog.log.level === 'success',
                            }"
                        >
                            {{
                                displayLog.log.message.substring(
                                    0,
                                    props.lineLength,
                                )
                            }}
                            <span
                                v-if="
                                    displayLog.log.message.length >
                                    props.lineLength
                                "
                                @click="
                                    openedLog[displayLog.log.id] =
                                        !openedLog[displayLog.log.id]
                                "
                                class="cursor-pointer tracking-tighter text-slate-400 hover:text-slate-300"
                                >[…]</span
                            >
                        </span>
                        <Badge
                            v-if="displayLog.log.data.table"
                            variant="outline"
                            class="ml-2 border-slate-700 text-xs text-slate-400"
                        >
                            {{ displayLog.log.data.table }}
                        </Badge>
                        <div
                            v-if="
                                displayLog.log.message.length >
                                    props.lineLength && openedLog[displayLog.log.id]
                            "
                        >
                            {{
                                displayLog.log.message.substring(props.lineLength)
                            }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Finished indicator -->
            <div
                v-if="finishedAt && !isActive"
                class="mt-4 border-t border-slate-800 pt-4"
            >
                <span class="text-slate-500">
                    --- Finished at {{ formatTime(finishedAt) }} ---
                </span>
            </div>
        </div>
    </div>
</template>
