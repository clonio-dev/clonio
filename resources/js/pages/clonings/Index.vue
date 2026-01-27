<script setup lang="ts">
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
import type { BreadcrumbItem } from '@/types';
import type { Cloning, CloningsIndexProps } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calendar,
    Clock,
    Copy,
    Database,
    MoreHorizontal,
    Pencil,
    Play,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<CloningsIndexProps>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Clonings',
        href: '/clonings',
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
    router.post(`/clonings/${cloning.id}/execute`);
}

function deleteCloning(cloning: Cloning) {
    if (
        confirm(
            `Are you sure you want to delete "${cloning.title}"? This cannot be undone.`,
        )
    ) {
        router.delete(`/clonings/${cloning.id}`);
    }
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
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500/20 to-purple-500/20 ring-1 ring-violet-500/30 dark:from-violet-500/10 dark:to-purple-500/10"
                        >
                            <Copy
                                class="size-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
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

                <Button
                    as-child
                    class="group gap-2 bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-md shadow-violet-500/20 transition-all hover:from-violet-500 hover:to-purple-500 hover:shadow-lg hover:shadow-violet-500/30 dark:shadow-violet-500/10 dark:hover:shadow-violet-500/20"
                >
                    <Link href="/clonings/create">
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
                    No Cloning Configurations Yet
                </h2>

                <p class="mx-auto mb-8 max-w-md text-sm text-muted-foreground">
                    Create your first cloning configuration to start anonymizing
                    and transferring data between your databases.
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
                                    Title
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase md:table-cell"
                                >
                                    Source
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase md:table-cell"
                                >
                                    Target
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Runs
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase lg:table-cell"
                                >
                                    Schedule
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
                                            :href="`/clonings/${cloning.id}`"
                                            class="font-medium text-foreground hover:text-violet-600 dark:hover:text-violet-400"
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

                                <!-- Source -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap md:table-cell"
                                >
                                    <div class="flex items-center gap-2">
                                        <Database
                                            class="size-4 text-muted-foreground/60"
                                        />
                                        <span class="text-sm text-foreground">
                                            {{
                                                cloning.source_connection
                                                    ?.name || '-'
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Target -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap md:table-cell"
                                >
                                    <div class="flex items-center gap-2">
                                        <ArrowRight
                                            class="size-3 text-muted-foreground/50"
                                        />
                                        <Database
                                            class="size-4 text-muted-foreground/60"
                                        />
                                        <span class="text-sm text-foreground">
                                            {{
                                                cloning.target_connection
                                                    ?.name || '-'
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Runs -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap lg:table-cell"
                                >
                                    {{ cloning.runs_count || 0 }}
                                </td>

                                <!-- Schedule -->
                                <td
                                    class="hidden px-4 py-4 whitespace-nowrap lg:table-cell"
                                >
                                    <div
                                        v-if="cloning.is_scheduled"
                                        class="flex items-center gap-1.5 text-sm text-muted-foreground"
                                    >
                                        <Clock class="size-3.5" />
                                        {{ cloning.schedule }}
                                        {{
                                            cloning.next_run_at
                                                ? ' (next run at ' +
                                                  formatDate(
                                                      cloning.next_run_at,
                                                  ) +
                                                  ')'
                                                : ''
                                        }}
                                    </div>
                                    <span
                                        v-else
                                        class="text-sm text-muted-foreground"
                                    >
                                        Manual
                                    </span>
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
                                                        :href="`/clonings/${cloning.id}`"
                                                    >
                                                        <Calendar
                                                            class="mr-2 size-4"
                                                        />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <Link
                                                        :href="`/clonings/${cloning.id}/edit`"
                                                    >
                                                        <Pencil
                                                            class="mr-2 size-4"
                                                        />
                                                        Edit
                                                    </Link>
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
