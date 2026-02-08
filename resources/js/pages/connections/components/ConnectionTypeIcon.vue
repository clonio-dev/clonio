<script setup lang="ts">
import {
    MariadbIcon,
    MysqlIcon,
    PostgresqlIcon,
    SqliteIcon,
    SqlserverIcon,
} from '@/components/icons/databases';
import { Database } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

interface Props {
    type: string | null | undefined;
    size?: string;
}

const props = defineProps<Props>();

interface DatabaseTypeConfig {
    label: string;
    icon: Component;
    iconBg: string;
}

const databaseTypeConfigs: Record<string, DatabaseTypeConfig> = {
    mysql: {
        label: 'MySQL',
        icon: MysqlIcon,
        iconBg: 'bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/30 dark:to-amber-950/30',
    },
    mariadb: {
        label: 'MariaDB',
        icon: MariadbIcon,
        iconBg: 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30',
    },
    pgsql: {
        label: 'PostgreSQL',
        icon: PostgresqlIcon,
        iconBg: 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30',
    },
    sqlserver: {
        label: 'SQL Server',
        icon: SqlserverIcon,
        iconBg: 'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30',
    },
    sqlite: {
        label: 'SQLite',
        icon: SqliteIcon,
        iconBg: 'bg-gradient-to-br from-sky-50 to-blue-50 dark:from-sky-950/30 dark:to-blue-950/30',
    },
};

const databaseTypeConfig = computed(() => {
    return (
        databaseTypeConfigs[props.type] || {
            label: props.type,
            icon: Database,
            iconBg: 'bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-950/30 dark:to-slate-950/30 text-muted-foreground/60',
        }
    );
});

interface IconSizeConfig {
    square: string;
    icon: string;
}

const sizes: Record<string, IconSizeConfig> = {
    '4': {
        square: 'size-4',
        icon: 'size-3',
    },
    '6': {
        square: 'size-6',
        icon: 'size-4',
    },
    '8': {
        square: 'size-8',
        icon: 'size-5',
    },
    '10': {
        square: 'size-10',
        icon: 'size-6',
    },
};

const cssClasses = computed(() => {
    return (
        sizes[props.size ?? '10'] || {
            square: 'size-10',
            icon: 'size-6',
        }
    );
});

const isCompact = computed(() => props.size === '4');
</script>

<template>
    <div
        v-if="isCompact"
        class="flex shrink-0 items-center justify-center"
        :class="cssClasses.square"
    >
        <component :is="databaseTypeConfig.icon" :class="cssClasses.icon" />
    </div>
    <div
        v-else
        class="flex shrink-0 items-center justify-center rounded-xl ring-1 ring-black/5 transition-transform duration-300 group-hover:scale-105 dark:ring-white/10"
        :class="cssClasses.square + ' ' + databaseTypeConfig.iconBg"
    >
        <component :is="databaseTypeConfig.icon" :class="cssClasses.icon" />
    </div>
</template>
