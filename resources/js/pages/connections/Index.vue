<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import InfoComponent from '@/components/InfoComponent.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import ConnectionCard from '@/pages/connections/components/ConnectionCard.vue';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import ConnectionsEmptyState from '@/pages/connections/components/ConnectionsEmptyState.vue';
import { Connection } from '@/pages/connections/types';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, RefreshCcw, ShieldCheckIcon } from 'lucide-vue-next';
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
        title: 'Connections',
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
        <Head title="Connection Management" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Connection Management
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Configure and monitor database nodes for cloning
                        operations.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        variant="secondary"
                        class="group gap-1"
                        @click="
                            router.post(
                                DatabaseConnectionController.testAllConnections()
                                    .url,
                            )
                        "
                        :disabled="!hasConnections"
                    >
                        <RefreshCcw
                            class="size-4 transition-transform group-hover:animate-spin"
                        />
                        Test all
                    </Button>
                    <Button
                        v-if="hasConnections"
                        @click="openCreateSheet"
                        class="group gap-1"
                    >
                        <Plus
                            class="size-4 transition-transform group-hover:rotate-90"
                        />
                        Add Connection
                    </Button>
                </div>
            </div>

            <!-- Empty State -->
            <ConnectionsEmptyState
                v-if="!hasConnections"
                @create="openCreateSheet"
            />

            <!-- Connections Grid -->
            <div v-else class="space-y-6">
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <ConnectionCard
                        v-for="(connection, index) in props.connections.data"
                        :key="connection.id"
                        :connection="connection"
                        :style="{ animationDelay: `${index * 50}ms` }"
                        class="animate-in fade-in-0 fill-mode-both slide-in-from-bottom-4"
                    />
                </div>

                <Pagination
                    :links="props.connections.links"
                    :current-page="props.connections.current_page"
                    :last-page="props.connections.last_page"
                    :prev-url="props.connections.prev_page_url"
                    :next-url="props.connections.next_page_url"
                    :from="props.connections.from"
                    :to="props.connections.to"
                    :total="totalConnections"
                    name="connection"
                    plural-name="connections"
                    variant="simple"
                />

                <InfoComponent
                    title="Security &amp; Privacy"
                    :icon="ShieldCheckIcon"
                    description="All database credentials are encrypted at rest using AES-256. Sensitive information such as passwords and secret keys are never displayed in the interface after initial configuration. Access to these profiles is governed by Clonio's policy."
                    class="mt-12"
                />
            </div>
        </div>

        <ConnectionFormSheet :open="sheetOpen" @close="closeSheet" />
    </AppLayout>
</template>
