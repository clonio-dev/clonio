<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import CloningRunController from '@/actions/App/Http/Controllers/CloningRunController';
import CloningRunStatusBadge from '@/components/cloning-runs/CloningRunStatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import AppLayout from '@/layouts/AppLayout.vue';
import { convertDuration } from '@/lib/date';
import ConnectionTypeIcon from '@/pages/connections/components/ConnectionTypeIcon.vue';
import type { BreadcrumbItem } from '@/types';
import type { CloningRun, CloningRunsIndexProps } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Plus, RefreshCw, Send } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<CloningRunsIndexProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Cloning Runs',
        href: '/cloning-runs',
    },
];

const isRefreshing = ref(false);

const hasRuns = computed(() => props.runs.data.length > 0);
const totalRuns = computed(() => props.runs.total);

function isActiveRun(run: CloningRun): boolean {
    return ['queued', 'processing'].includes(run.status);
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDuration(run: CloningRun): string {
    if (!run.started_at) return '-';

    const start = new Date(run.started_at);
    const end = run.finished_at ? new Date(run.finished_at) : new Date();

    return convertDuration(start, end);
}

function refreshPage() {
    isRefreshing.value = true;
    router.reload({
        only: ['runs', 'hasActiveRuns'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

useAutoRefresh(
    refreshPage,
    computed(() => props.hasActiveRuns),
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Cloning Runs" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Cloning Runs
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        View and manage all your database cloning runs
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Auto-refresh indicator -->
                    <div
                        v-if="hasActiveRuns"
                        class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <div
                            class="size-2 animate-pulse rounded-full bg-emerald-500"
                        />
                        <span>Live</span>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        @click="refreshPage"
                        :disabled="isRefreshing || hasActiveRuns"
                        class="gap-2"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': isRefreshing }"
                        />
                        Refresh
                    </Button>

                    <Button as-child class="group gap-2">
                        <Link :href="CloningController.create().url">
                            <Plus
                                class="size-4 transition-transform group-hover:rotate-90"
                            />
                            New Cloning
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="!hasRuns"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/60 bg-gradient-to-b from-muted/20 to-muted/40 px-6 py-20 text-center dark:border-border/40 dark:from-muted/10 dark:to-muted/20"
            >
                <div
                    class="mb-6 flex size-20 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/20 to-purple-500/20 ring-1 ring-violet-500/20 dark:from-violet-500/10 dark:to-purple-500/10"
                >
                    <Send
                        class="size-10 text-violet-600 dark:text-violet-400"
                    />
                </div>

                <h2
                    class="mb-2 text-xl font-semibold tracking-tight text-foreground"
                >
                    No Cloning Runs Yet
                </h2>

                <p class="mx-auto mb-8 max-w-md text-sm text-muted-foreground">
                    Create a cloning configuration and run it to anonymize and
                    transfer data between your databases securely.
                </p>

                <Button
                    as-child
                    class="gap-2 bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-md shadow-violet-500/20 hover:from-violet-500 hover:to-purple-500"
                >
                    <Link href="/clonings/create">
                        <Plus class="size-4" />
                        Create First Cloning
                    </Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="space-y-6">
                <div
                    class="overflow-hidden rounded-xl border border-border/60 bg-card dark:border-border/40"
                >
                    <table class="w-full">
                        <thead>
                            <tr
                                class="border-b border-border/60 bg-muted/30 dark:border-border/40 dark:bg-muted/20"
                            >
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Run
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase md:table-cell"
                                >
                                    Source / Target
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Started
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Duration
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-border/40 dark:divide-border/30"
                        >
                            <tr
                                v-for="run in runs.data"
                                :key="run.id"
                                class="group relative transition-colors hover:bg-muted/30 dark:hover:bg-muted/20"
                                :class="{
                                    'bg-emerald-50/50 dark:bg-emerald-950/20':
                                        isActiveRun(run),
                                }"
                            >
                                <!-- Run ID & Cloning -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div
                                            v-if="isActiveRun(run)"
                                            class="relative flex size-2"
                                        >
                                            <span
                                                class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                            ></span>
                                            <span
                                                class="relative inline-flex size-2 rounded-full bg-emerald-500"
                                            ></span>
                                        </div>
                                        <Link
                                            :href="
                                                CloningRunController.show(run)
                                                    .url
                                            "
                                        >
                                            <span
                                                class="font-mono text-sm font-semibold text-foreground"
                                            >
                                                #{{ run.id }}
                                            </span>
                                            <div
                                                v-if="run.cloning"
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ run.cloning.title }}
                                            </div>
                                        </Link>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                    :title="
                                        run.status === 'processing'
                                            ? run.progress_percent + '%'
                                            : ''
                                    "
                                >
                                    <CloningRunStatusBadge
                                        :status="run.status"
                                    />

                                    <!-- Progress bar for processing -->
                                    <div
                                        v-if="run.status === 'processing'"
                                        class="mt-2 h-1 w-24 overflow-hidden rounded-full bg-emerald-200 dark:bg-emerald-900/50"
                                    >
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
                                            :style="{
                                                width: `${run.progress_percent}%`,
                                            }"
                                        />
                                    </div>
                                </td>

                                <!-- Source / Target -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap md:table-cell"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            <ConnectionTypeIcon
                                                :type="
                                                    run.cloning
                                                        ?.source_connection
                                                        ?.type
                                                "
                                                size="4"
                                            />
                                            <span
                                                class="text-sm text-foreground"
                                            >
                                                {{
                                                    run.cloning
                                                        ?.source_connection
                                                        ?.name || '-'
                                                }}
                                            </span>
                                        </div>
                                        <ArrowRight
                                            class="size-3 text-muted-foreground/50"
                                        />
                                        <div class="flex items-center gap-1">
                                            <ConnectionTypeIcon
                                                :type="
                                                    run.cloning
                                                        ?.target_connection
                                                        ?.type
                                                "
                                                size="4"
                                            />
                                            <span
                                                class="text-sm text-foreground"
                                            >
                                                {{
                                                    run.cloning
                                                        ?.target_connection
                                                        ?.name || '-'
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Started -->
                                <td
                                    class="hidden px-4 py-4 text-sm whitespace-nowrap text-muted-foreground lg:table-cell"
                                >
                                    {{ formatDate(run.started_at) }}
                                </td>

                                <!-- Duration -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap lg:table-cell"
                                >
                                    <span
                                        class="text-sm text-muted-foreground tabular-nums"
                                    >
                                        {{ formatDuration(run) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td
                                    class="px-4 py-4 text-right whitespace-nowrap"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        as-child
                                        class="text-muted-foreground transition-colors hover:text-foreground"
                                    >
                                        <Link
                                            :href="
                                                CloningRunController.show(run)
                                                    .url
                                            "
                                        >
                                            View
                                            <ArrowRight class="ml-1 size-4" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr
                                class="border-t border-border/60 bg-muted/30 dark:border-border/40 dark:bg-muted/20"
                            >
                                <td
                                    colspan="6"
                                    class="px-4 py-3 text-xs tracking-wider text-muted-foreground"
                                >
                                    <Pagination
                                        :links="runs.links"
                                        :current-page="runs.current_page"
                                        :last-page="runs.last_page"
                                        :prev-url="runs.prev_page_url"
                                        :next-url="runs.next_page_url"
                                        :from="runs.from"
                                        :to="runs.to"
                                        :total="totalRuns"
                                        name="cloning run"
                                        plural-name="cloning runs"
                                    />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
