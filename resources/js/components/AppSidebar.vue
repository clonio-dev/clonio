<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import TransferRunController from '@/actions/App/Http/Controllers/TransferRunController';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    DatabaseIcon,
    FlaskConicalIcon,
    LayoutGridIcon,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: TransferRunController.dashboard().url,
        icon: LayoutGridIcon,
    },
    {
        title: 'Transfers',
        href: TransferRunController.index().url,
        icon: LayoutGridIcon,
        isActive: page.url.startsWith('/transfers'),
    },
    {
        title: 'Database Connections',
        href: DatabaseConnectionController.index().url,
        icon: DatabaseIcon,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Queue',
        href: '/queue',
        icon: FlaskConicalIcon,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="TransferRunController.dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
