<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import CloningRunStatusBadge from '@/components/cloning-runs/CloningRunStatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import ConnectionsPagination from '@/pages/connections/components/ConnectionsPagination.vue';
import ConnectionTypeIcon from '@/pages/connections/components/ConnectionTypeIcon.vue';
import type { BreadcrumbItem } from '@/types';
import type { Cloning, CloningsIndexProps } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calendar,
    Clock,
    Copy,
    MoreHorizontal,
    Pause,
    Pencil,
    Play,
    PlayCircle,
    Plus,
    Trash2,
    UserIcon,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<CloningsIndexProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Clonings',
        href: CloningController.index().url,
    },
];

const hasClonings = computed(() => props.clonings.data.length > 0);
const totalClonings = computed(() => props.clonings.total);

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function executeCloning(cloning: Cloning) {
    router.post(CloningController.execute(cloning).url);
}

function deleteCloning(cloning: Cloning) {
    if (
        confirm(
            `Are you sure you want to delete "${cloning.title}"? This cannot be undone.`,
        )
    ) {
        router.delete(CloningController.destroy(cloning).url);
    }
}

function pauseCloning(cloning: Cloning) {
    router.post(CloningController.pause(cloning).url);
}

function resumeCloning(cloning: Cloning) {
    router.post(CloningController.resume(cloning).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Clonings" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Clonings
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Manage your database cloning configurations
                    </p>
                </div>

                <Button as-child class="group gap-2">
                    <Link :href="CloningController.create().url">
                        <Plus
                            class="size-4 transition-transform group-hover:rotate-90"
                        />
                        New Cloning
                    </Link>
                </Button>
            </div>

            <!-- Empty State -->
            <div
                v-if="!hasClonings"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/60 bg-gradient-to-b from-muted/20 to-muted/40 px-6 py-20 text-center dark:border-border/40 dark:from-muted/10 dark:to-muted/20"
            >
                <div
                    class="mb-6 flex size-20 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/20 to-purple-500/20 ring-1 ring-violet-500/20 dark:from-violet-500/10 dark:to-purple-500/10"
                >
                    <Copy
                        class="size-10 text-violet-600 dark:text-violet-400"
                    />
                </div>

                <h2
                    class="mb-2 text-xl font-semibold tracking-tight text-foreground"
                >
                    No Clonings yet
                </h2>

                <p class="mx-auto mb-8 max-w-md text-sm text-muted-foreground">
                    Create your first cloning to start anonymizing and
                    transferring data between your databases.
                </p>

                <Button as-child>
                    <Link :href="CloningController.create().url">
                        Create First Cloning
                    </Link>
                </Button>
            </div>

            <!-- Table -->
            <div v-else class="space-y-6">
                <div
                    class="overflow-x-auto rounded-xl border border-border/60 bg-card dark:border-border/40"
                >
                    <table class="w-full min-w-[640px]">
                        <thead>
                            <tr
                                class="border-b border-border/60 bg-muted/30 dark:border-border/40 dark:bg-muted/20"
                            >
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Title
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase md:table-cell"
                                >
                                    Source / Target
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-right text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Runs
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Trigger
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Last Run
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
                                v-for="cloning in clonings.data"
                                :key="cloning.id"
                                class="group transition-colors hover:bg-muted/30 dark:hover:bg-muted/20"
                            >
                                <!-- Title -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <Link
                                            :href="
                                                CloningController.show(cloning)
                                                    .url
                                            "
                                            class="font-medium text-foreground hover:text-primary dark:hover:text-primary"
                                        >
                                            {{ cloning.title }}
                                        </Link>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            Created
                                            {{ formatDate(cloning.created_at) }}
                                        </span>
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
                                                    cloning.source_connection
                                                        ?.type
                                                "
                                                size="4"
                                            />
                                            <span
                                                class="text-sm text-foreground"
                                            >
                                                {{
                                                    cloning.source_connection
                                                        ?.name || '-'
                                                }}
                                            </span>
                                        </div>

                                        <ArrowRight
                                            class="size-3 text-muted-foreground/50"
                                        />

                                        <!-- Target -->
                                        <div class="flex items-center gap-1">
                                            <ConnectionTypeIcon
                                                :type="
                                                    cloning.target_connection
                                                        ?.type
                                                "
                                                size="4"
                                            />
                                            <span
                                                class="text-sm text-foreground"
                                            >
                                                {{
                                                    cloning.target_connection
                                                        ?.name || '-'
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Runs -->
                                <td
                                    class="hidden px-4 py-4 text-right whitespace-nowrap slashed-zero tabular-nums lg:table-cell"
                                >
                                    {{ cloning.runs_count || 0 }}
                                </td>

                                <!-- Trigger -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap lg:table-cell"
                                >
                                    <!-- Paused state -->
                                    <div
                                        v-if="
                                            cloning.is_scheduled &&
                                            cloning.is_paused
                                        "
                                        class="flex flex-col"
                                    >
                                        <div
                                            class="flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400"
                                        >
                                            <Pause class="size-3.5" />
                                            Paused
                                        </div>
                                        <div
                                            v-if="
                                                cloning.consecutive_failures > 0
                                            "
                                            class="text-xs text-muted-foreground/80"
                                        >
                                            ({{ cloning.consecutive_failures }}
                                            failures)
                                        </div>
                                    </div>
                                    <!-- Active triggers -->
                                    <div
                                        v-else-if="
                                            cloning.is_scheduled ||
                                            cloning.trigger_config?.api_trigger
                                                ?.enabled
                                        "
                                        class="flex flex-col gap-1"
                                    >
                                        <div
                                            v-if="cloning.is_scheduled"
                                            class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                        >
                                            <Clock
                                                class="size-3.5 shrink-0"
                                            />
                                            {{ cloning.schedule }}
                                        </div>
                                        <div
                                            v-if="
                                                cloning.trigger_config
                                                    ?.api_trigger?.enabled
                                            "
                                            class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                        >
                                            <Zap class="size-3.5 shrink-0" />
                                            <span
                                                class="rounded bg-violet-100 px-1.5 py-0.5 text-xs font-medium text-violet-700 dark:bg-violet-500/20 dark:text-violet-300"
                                            >
                                                API
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Manual only -->
                                    <span
                                        v-else
                                        class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                    >
                                        <UserIcon class="size-3.5" />
                                        Manual
                                    </span>
                                </td>

                                <!-- Last Run -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap lg:table-cell"
                                >
                                    <template v-if="cloning.last_run">
                                        <CloningRunStatusBadge
                                            :status="
                                                cloning.last_run?.status ||
                                                'queued'
                                            "
                                        />
                                    </template>
                                </td>

                                <!-- Actions -->
                                <td
                                    class="px-4 py-4 text-right whitespace-nowrap"
                                >
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="executeCloning(cloning)"
                                            class="gap-1.5"
                                        >
                                            <Play class="size-3.5" />
                                            Run
                                        </Button>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="size-8 p-0"
                                                >
                                                    <MoreHorizontal
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="
                                                            CloningController.show(
                                                                cloning,
                                                            ).url
                                                        "
                                                    >
                                                        <Calendar
                                                            class="mr-2 size-4"
                                                        />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="
                                                            CloningController.edit(
                                                                cloning,
                                                            ).url
                                                        "
                                                    >
                                                        <Pencil
                                                            class="mr-2 size-4"
                                                        />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    @click="
                                                        executeCloning(cloning)
                                                    "
                                                >
                                                    <Play class="mr-2 size-4" />
                                                    Run
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="
                                                        cloning.is_scheduled &&
                                                        !cloning.is_paused
                                                    "
                                                    @click="
                                                        pauseCloning(cloning)
                                                    "
                                                >
                                                    <Pause
                                                        class="mr-2 size-4"
                                                    />
                                                    Pause Schedule
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="
                                                        cloning.is_scheduled &&
                                                        cloning.is_paused
                                                    "
                                                    @click="
                                                        resumeCloning(cloning)
                                                    "
                                                >
                                                    <PlayCircle
                                                        class="mr-2 size-4"
                                                    />
                                                    Resume Schedule
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400"
                                                    @click="
                                                        deleteCloning(cloning)
                                                    "
                                                >
                                                    <Trash2
                                                        class="mr-2 size-4"
                                                    />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr
                                class="border-t border-border/60 bg-muted/30 dark:border-border/40 dark:bg-muted/20"
                            >
                                <td
                                    colspan="7"
                                    class="px-4 py-3 text-xs tracking-wider text-muted-foreground"
                                >
                                    <Pagination
                                        :links="clonings.links"
                                        :current-page="clonings.current_page"
                                        :last-page="clonings.last_page"
                                        :prev-url="clonings.prev_page_url"
                                        :next-url="clonings.next_page_url"
                                        :from="clonings.from"
                                        :to="clonings.to"
                                        :total="totalClonings"
                                        name="clone"
                                        plural-name="clones"
                                    />
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <ConnectionsPagination
                    v-if="clonings.last_page > 1"
                    :links="clonings.links"
                    :current-page="clonings.current_page"
                    :last-page="clonings.last_page"
                    :prev-url="null"
                    :next-url="null"
                />
            </div>
        </div>
    </AppLayout>
</template>
