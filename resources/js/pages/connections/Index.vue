<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import ConnectionCard from '@/pages/connections/components/ConnectionCard.vue';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import ConnectionsEmptyState from '@/pages/connections/components/ConnectionsEmptyState.vue';
import ConnectionsPagination from '@/pages/connections/components/ConnectionsPagination.vue';
import { Connection } from '@/pages/connections/types';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Database, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    connections: {
        data: Connection[];
        current_page: number;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        links: {
            url: string | null;
            label: string;
            page: number | null;
            active: boolean;
        }[];
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Database Connections',
        href: DatabaseConnectionController.index().url,
    },
];

const sheetOpen = ref(false);

const hasConnections = computed(() => props.connections.data.length > 0);
const totalConnections = computed(() => props.connections.total);

function openCreateSheet() {
    sheetOpen.value = true;
}

function closeSheet() {
    sheetOpen.value = false;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Database Connections" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500/20 to-teal-500/20 ring-1 ring-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10"
                        >
                            <Database
                                class="size-5 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Database Connections
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Manage your database connections for data transfers
                    </p>
                </div>

                <Button
                    v-if="hasConnections"
                    @click="openCreateSheet"
                    class="group gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20 transition-all hover:from-emerald-500 hover:to-teal-500 hover:shadow-lg hover:shadow-emerald-500/30 dark:shadow-emerald-500/10 dark:hover:shadow-emerald-500/20"
                >
                    <Plus
                        class="size-4 transition-transform group-hover:rotate-90"
                    />
                    Add Connection
                </Button>
            </div>

            <!-- Stats Bar -->
            <div
                v-if="hasConnections"
                class="mb-6 flex items-center gap-6 rounded-lg border border-border/50 bg-gradient-to-r from-muted/30 to-muted/50 px-4 py-3 dark:from-muted/20 dark:to-muted/30"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="size-2 animate-pulse rounded-full bg-emerald-500"
                    ></div>
                    <span class="text-sm font-medium text-foreground">
                        {{ totalConnections }}
                        {{ totalConnections === 1 ? 'connection' : 'connections' }}
                    </span>
                </div>
                <div class="h-4 w-px bg-border"></div>
                <span class="text-sm text-muted-foreground">
                    Showing {{ props.connections.from }}-{{ props.connections.to }} of
                    {{ totalConnections }}
                </span>
            </div>

            <!-- Empty State -->
            <ConnectionsEmptyState
                v-if="!hasConnections"
                @create="openCreateSheet"
            />

            <!-- Connections Grid -->
            <div v-else class="space-y-6">
                <div
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
                >
                    <ConnectionCard
                        v-for="(connection, index) in props.connections.data"
                        :key="connection.id"
                        :connection="connection"
                        :style="{ animationDelay: `${index * 50}ms` }"
                        class="animate-in fade-in-0 slide-in-from-bottom-4 fill-mode-both"
                    />
                </div>

                <!-- Pagination -->
                <ConnectionsPagination
                    v-if="props.connections.last_page > 1"
                    :links="props.connections.links"
                    :current-page="props.connections.current_page"
                    :last-page="props.connections.last_page"
                    :prev-url="props.connections.prev_page_url"
                    :next-url="props.connections.next_page_url"
                />
            </div>
        </div>

        <ConnectionFormSheet :open="sheetOpen" @close="closeSheet" />
    </AppLayout>
</template>
