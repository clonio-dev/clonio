<script setup lang="ts">
import CloningRunStatusBadge from '@/components/cloning-runs/CloningRunStatusBadge.vue';
import { Card, CardContent } from '@/components/ui/card';
import { convertDuration } from '@/lib/date';
import type { CloningRun } from '@/types/cloning.types';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    run: CloningRun;
    isActive?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isActive: false,
});

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

    return convertDuration(start, end);
});
</script>

<template>
    <Link :href="`/cloning-runs/${run.id}`">
        <Card
            class="group h-full cursor-pointer border-border/60 transition-all hover:border-border hover:shadow-md dark:border-border/40 dark:hover:border-border/60"
            :class="{ 'ring-2 ring-emerald-500/20': isActive }"
        >
            <CardContent class="p-4 py-0">
                <div class="mb-3 flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex shrink-0 items-center justify-center rounded-lg"
                        >
                            {{ `#${run.id}` }}
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
                    <CloningRunStatusBadge :status="props.run.status" />
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
                    <div class="flex items-center gap-1">
                        <span v-if="run.cloning?.source_connection">
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
