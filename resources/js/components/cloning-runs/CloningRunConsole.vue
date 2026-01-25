<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { CloningRunLog } from '@/types/cloning.types';
import {
    AlertCircle,
    CheckCircle2,
    Info,
    Terminal,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    logs: CloningRunLog[];
    runId: number;
    isActive: boolean;
    finishedAt: string | null;
}

const props = defineProps<Props>();

const consoleRef = ref<HTMLDivElement | null>(null);
const autoScroll = ref(true);

const levelConfig: Record<
    string,
    { icon: typeof Info; class: string; badge: string }
> = {
    info: {
        icon: Info,
        class: 'text-blue-600 dark:text-blue-400',
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400',
    },
    success: {
        icon: CheckCircle2,
        class: 'text-green-600 dark:text-green-400',
        badge: 'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400',
    },
    warning: {
        icon: AlertCircle,
        class: 'text-amber-600 dark:text-amber-400',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
    },
    error: {
        icon: XCircle,
        class: 'text-red-600 dark:text-red-400',
        badge: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
    },
};

const sortedLogs = computed(() => {
    return [...props.logs].sort(
        (a, b) =>
            new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
    );
});

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
                </Badge>
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

            <div
                v-for="log in sortedLogs"
                :key="log.id"
                class="group mb-2 flex gap-3"
            >
                <!-- Timestamp -->
                <span class="shrink-0 text-slate-500 tabular-nums">
                    {{ formatTime(log.created_at) }}
                </span>

                <!-- Level indicator -->
                <component
                    :is="levelConfig[log.level]?.icon || Info"
                    class="mt-0.5 size-4 shrink-0"
                    :class="levelConfig[log.level]?.class || 'text-slate-400'"
                />

                <!-- Message -->
                <div class="flex-1">
                    <span
                        class="text-slate-200"
                        :class="{
                            'text-red-400': log.level === 'error',
                            'text-amber-500': log.level === 'warning',
                            'text-blue-400': log.level === 'info',
                            'text-green-400': log.level === 'success',
                        }"
                    >
                        {{ log.message }}
                    </span>
                    <Badge
                        v-if="log.data.table"
                        variant="outline"
                        class="ml-2 border-slate-700 text-xs text-slate-400"
                    >
                        {{ log.data.table }}
                    </Badge>
                </div>
            </div>

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
