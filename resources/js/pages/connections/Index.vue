<script setup lang="ts">
import IndexController from '@/actions/App/Http/Controllers/DatabaseConnections/IndexController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import { Connection } from '@/pages/connections/types';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

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
        href: IndexController.url(),
    },
];

const open = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Database Connections" />

        <div class="px-4 py-6">
            <Heading title="All known database connections" />

            <Button type="button" class="mb-4" @click="open = true"
                >Create new connection</Button
            >

            <table class="w-full whitespace-nowrap">
                <thead></thead>
                <tbody>
                    <tr
                        v-for="connection in props.connections.data"
                        :key="connection.id"
                    >
                        <td>{{ connection.name }}</td>
                        <td>{{ connection.type }}</td>
                        <td>
                            {{ connection.username }}@{{ connection.host }}:{{
                                connection.port
                            }}/{{ connection.database }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Paginate</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <ConnectionFormSheet :open="open" />
    </AppLayout>
</template>
