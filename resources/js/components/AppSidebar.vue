<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import CloningRunController from '@/actions/App/Http/Controllers/CloningRunController';
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
    LayoutGridIcon,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import CloningController from '@/actions/App/Http/Controllers/CloningController';

const page = usePage();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: CloningRunController.dashboard().url,
        icon: LayoutGridIcon,
        isActive: page.url.startsWith(CloningRunController.dashboard().url),
    },
    {
        title: 'Clones',
        href: CloningController.index().url,
        icon: LayoutGridIcon,
        isActive: page.url.startsWith(CloningController.index().url),
    },
    {
        title: 'Runs',
        href: CloningRunController.index().url,
        icon: LayoutGridIcon,
        isActive: page.url.startsWith(CloningRunController.index().url),
    },
    {
        title: 'Connections',
        href: DatabaseConnectionController.index().url,
        icon: DatabaseIcon,
        isActive: page.url.startsWith(DatabaseConnectionController.index().url),
    },
];

const footerNavItems: NavItem[] = [
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="CloningRunController.dashboard().url">
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
