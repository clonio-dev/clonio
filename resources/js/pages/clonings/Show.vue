<script setup lang="ts">
import RunCard from '@/components/cloning-runs/RunCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { CloningRun, CloningShowProps } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    Calendar,
    Clock,
    Copy,
    Database,
    Pause,
    Pencil,
    Play,
    PlayCircle,
    Settings,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<CloningShowProps>();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Clonings',
        href: '/clonings',
    },
    {
        title: props.cloning.title,
        href: `/clonings/${props.cloning.id}`,
    },
]);

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function executeCloning() {
    router.post(`/clonings/${props.cloning.id}/execute`);
}

function deleteCloning() {
    if (
        confirm(
            `Are you sure you want to delete "${props.cloning.title}"? This cannot be undone.`,
        )
    ) {
        router.delete(`/clonings/${props.cloning.id}`);
    }
}

function pauseCloning() {
    router.post(`/clonings/${props.cloning.id}/pause`);
}

function resumeCloning() {
    router.post(`/clonings/${props.cloning.id}/resume`);
}

// Map runs to include cloning data for RunCard compatibility
const runsWithCloning = computed(() =>
    props.runs.map((run: CloningRun) => ({
        ...run,
        cloning: props.cloning,
    })),
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="cloning.title" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500/20 to-purple-500/20 ring-1 ring-violet-500/30 dark:from-violet-500/10 dark:to-purple-500/10"
                        >
                            <Copy
                                class="size-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                        <div>
                            <h1
                                class="text-2xl font-semibold tracking-tight text-foreground"
                            >
                                {{ cloning.title }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Created {{ formatDate(cloning.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="executeCloning"
                        class="gap-2"
                    >
                        <Play class="size-4" />
                        Run Now
                    </Button>

                    <Button variant="outline" size="sm" as-child class="gap-2">
                        <Link :href="`/clonings/${cloning.id}/edit`">
                            <Pencil class="size-4" />
                            Edit
                        </Link>
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        @click="deleteCloning"
                        class="gap-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Configuration Details -->
                <div class="space-y-6 lg:col-span-1">
                    <!-- Connection Info -->
                    <Card
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Database
                                    class="size-4 text-muted-foreground"
                                />
                                Connections
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div
                                    class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Source
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                                >
                                    <Database
                                        class="size-4 text-muted-foreground/70"
                                    />
                                    <span class="font-medium text-foreground">
                                        {{
                                            cloning.source_connection?.name ||
                                            'Unknown'
                                        }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="ml-auto text-xs"
                                    >
                                        {{ cloning.source_connection?.type }}
                                    </Badge>
                                </div>
                            </div>

                            <div
                                class="flex justify-center text-muted-foreground"
                            >
                                <ArrowRight class="size-4 rotate-90" />
                            </div>

                            <div class="space-y-2">
                                <div
                                    class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Target
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                                >
                                    <Database
                                        class="size-4 text-muted-foreground/70"
                                    />
                                    <span class="font-medium text-foreground">
                                        {{
                                            cloning.target_connection?.name ||
                                            'Unknown'
                                        }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="ml-auto text-xs"
                                    >
                                        {{ cloning.target_connection?.type }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Schedule Info -->
                    <Card
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Clock class="size-4 text-muted-foreground" />
                                Schedule
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <!-- Paused state -->
                            <div
                                v-if="cloning.is_scheduled && cloning.is_paused"
                                class="space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400"
                                >
                                    <AlertTriangle class="size-4" />
                                    <span class="text-sm font-medium">
                                        Schedule Paused
                                    </span>
                                </div>
                                <p
                                    v-if="cloning.consecutive_failures > 0"
                                    class="text-sm text-muted-foreground"
                                >
                                    Auto-paused after
                                    {{ cloning.consecutive_failures }}
                                    consecutive failure{{
                                        cloning.consecutive_failures === 1
                                            ? ''
                                            : 's'
                                    }}.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Schedule:
                                    <span class="font-mono">{{
                                        cloning.schedule
                                    }}</span>
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="resumeCloning"
                                    class="w-full gap-2"
                                >
                                    <PlayCircle class="size-4" />
                                    Resume Schedule
                                </Button>
                            </div>
                            <!-- Active scheduled state -->
                            <div
                                v-else-if="cloning.is_scheduled"
                                class="space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400"
                                >
                                    <Calendar class="size-4" />
                                    <span class="font-mono text-sm">
                                        {{ cloning.schedule }}
                                    </span>
                                </div>
                                <p
                                    v-if="cloning.next_run_at"
                                    class="text-sm text-muted-foreground"
                                >
                                    Next run:
                                    {{ formatDate(cloning.next_run_at) }}
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="pauseCloning"
                                    class="w-full gap-2"
                                >
                                    <Pause class="size-4" />
                                    Pause Schedule
                                </Button>
                            </div>
                            <!-- Manual only -->
                            <div
                                v-else
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <Settings class="size-4" />
                                <span class="text-sm"
                                    >Manual execution only</span
                                >
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Anonymization Config -->
                    <Card
                        v-if="cloning.anonymization_config"
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Settings
                                    class="size-4 text-muted-foreground"
                                />
                                Anonymization
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-sm text-muted-foreground">
                                Configuration applied to protect sensitive data
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Runs List -->
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-lg font-semibold tracking-tight text-foreground"
                        >
                            Recent Runs
                        </h2>
                        <Link
                            href="/cloning-runs"
                            class="text-sm text-muted-foreground hover:text-foreground"
                        >
                            View All
                        </Link>
                    </div>

                    <div
                        v-if="runs.length > 0"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <RunCard
                            v-for="run in runsWithCloning"
                            :key="run.id"
                            :run="run as any"
                            :is-active="
                                ['queued', 'processing'].includes(run.status)
                            "
                        />
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border/60 bg-muted/20 px-6 py-12 text-center dark:border-border/40"
                    >
                        <Play class="mb-4 size-10 text-muted-foreground/50" />
                        <h3 class="mb-1 font-medium text-foreground">
                            No Runs Yet
                        </h3>
                        <p class="mb-4 text-sm text-muted-foreground">
                            This cloning configuration hasn't been executed yet.
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="executeCloning"
                            class="gap-2"
                        >
                            <Play class="size-4" />
                            Run Now
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
