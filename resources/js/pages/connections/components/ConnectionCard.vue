<script setup lang="ts">
import {
    MariadbIcon,
    MysqlIcon,
    PostgresqlIcon,
    SqliteIcon,
    SqlserverIcon,
} from '@/components/icons/databases';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Connection } from '@/pages/connections/types';
import { router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Copy,
    Database,
    MoreVertical,
    Pencil,
    Server,
    Trash2,
    User,
} from 'lucide-vue-next';
import { computed, ref, type Component } from 'vue';
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';

interface Props {
    connection: Connection;
}

const props = defineProps<Props>();

const copied = ref(false);

interface DatabaseTypeConfig {
    label: string;
    icon: Component;
    badgeClass: string;
    iconBg: string;
    accentColor: string;
}

const databaseTypeConfigs: Record<string, DatabaseTypeConfig> = {
    mysql: {
        label: 'MySQL',
        icon: MysqlIcon,
        badgeClass:
            'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/50 dark:text-orange-400 dark:border-orange-900',
        iconBg:
            'bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/30 dark:to-amber-950/30',
        accentColor: 'from-orange-500 to-amber-500',
    },
    mariadb: {
        label: 'MariaDB',
        icon: MariadbIcon,
        badgeClass:
            'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-900',
        iconBg:
            'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30',
        accentColor: 'from-amber-500 to-orange-500',
    },
    pgsql: {
        label: 'PostgreSQL',
        icon: PostgresqlIcon,
        badgeClass:
            'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900',
        iconBg:
            'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30',
        accentColor: 'from-blue-500 to-indigo-500',
    },
    sqlserver: {
        label: 'SQL Server',
        icon: SqlserverIcon,
        badgeClass:
            'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-900',
        iconBg:
            'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30',
        accentColor: 'from-red-500 to-rose-500',
    },
    sqlite: {
        label: 'SQLite',
        icon: SqliteIcon,
        badgeClass:
            'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-950/50 dark:text-sky-400 dark:border-sky-900',
        iconBg:
            'bg-gradient-to-br from-sky-50 to-blue-50 dark:from-sky-950/30 dark:to-blue-950/30',
        accentColor: 'from-sky-500 to-blue-500',
    },
};

const databaseTypeConfig = computed(() => {
    return (
        databaseTypeConfigs[props.connection.type] || {
            label: props.connection.type,
            icon: Database,
            badgeClass:
                'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-950/50 dark:text-gray-400 dark:border-gray-900',
            iconBg:
                'bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-950/30 dark:to-slate-950/30',
            accentColor: 'from-gray-500 to-slate-500',
        }
    );
});

function deleteConnection() {
    if (confirm('Are you sure you want to delete this connection?')) {
        router.delete(DatabaseConnectionController.destroy(props.connection.id).url);
    }
}
</script>

<template>
    <Card
        class="group relative overflow-hidden border-border/60 bg-card transition-all duration-300 hover:border-border hover:shadow-lg hover:shadow-black/5 dark:border-border/40 dark:hover:shadow-black/20"
    >
        <!-- Accent line at top -->
        <div
            class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            :class="databaseTypeConfig.accentColor"
        />

        <CardHeader class="pb-4">
            <div class="flex items-start gap-4">
                <!-- Database icon -->
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-xl ring-1 ring-black/5 transition-transform duration-300 group-hover:scale-105 dark:ring-white/10"
                    :class="databaseTypeConfig.iconBg"
                >
                    <component
                        :is="databaseTypeConfig.icon"
                        class="size-8"
                    />
                </div>

                <div class="min-w-0 flex-1 space-y-1.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <CardTitle
                                class="truncate text-lg font-semibold leading-tight text-foreground"
                            >
                                {{ connection.name }}
                            </CardTitle>
                            <CardDescription class="mt-1 flex items-center gap-2">
                                <Badge
                                    variant="outline"
                                    class="text-xs font-medium"
                                    :class="databaseTypeConfig.badgeClass"
                                >
                                    {{ databaseTypeConfig.label }}
                                </Badge>
                                <Badge
                                    v-if="connection.is_production_stage"
                                    variant="outline"
                                    class="gap-1 border-amber-300 bg-amber-100 text-xs font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-400"
                                >
                                    <AlertTriangle class="size-3" />
                                    PROD
                                </Badge>
                            </CardDescription>
                        </div>

                        <!-- Actions dropdown -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 data-[state=open]:opacity-100"
                                >
                                    <MoreVertical class="size-4" />
                                    <span class="sr-only">Open menu</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem
                                    class="gap-2 text-destructive focus:bg-destructive/10 focus:text-destructive"
                                    @click="deleteConnection"
                                >
                                    <Trash2 class="size-4" />
                                    Delete connection
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 pt-0">
            <!-- Connection details -->
            <div class="grid gap-2">
                <!-- Host & Port -->
                <div
                    class="flex items-center gap-3 rounded-lg bg-muted/40 px-3 py-2 transition-colors hover:bg-muted/60 dark:bg-muted/20 dark:hover:bg-muted/30"
                >
                    <Server
                        class="size-4 shrink-0 text-muted-foreground/70"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-mono text-sm text-foreground"
                        >
                            {{ connection.host
                            }}<span class="text-muted-foreground">:</span
                            >{{ connection.port }}
                        </p>
                    </div>
                </div>

                <!-- Database -->
                <div
                    class="flex items-center gap-3 rounded-lg bg-muted/40 px-3 py-2 transition-colors hover:bg-muted/60 dark:bg-muted/20 dark:hover:bg-muted/30"
                >
                    <Database
                        class="size-4 shrink-0 text-muted-foreground/70"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-mono text-sm text-foreground"
                        >
                            {{ connection.database }}
                        </p>
                    </div>
                </div>

                <!-- Username -->
                <div
                    class="flex items-center gap-3 rounded-lg bg-muted/40 px-3 py-2 transition-colors hover:bg-muted/60 dark:bg-muted/20 dark:hover:bg-muted/30"
                >
                    <User class="size-4 shrink-0 text-muted-foreground/70" />
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-mono text-sm text-foreground"
                        >
                            {{ connection.username }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Copy feedback -->
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div
                    v-if="copied"
                    class="flex items-center justify-center gap-2 rounded-lg bg-emerald-500/10 py-2 text-sm font-medium text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400"
                >
                    <Check class="size-4" />
                    Copied to clipboard
                </div>
            </Transition>
        </CardContent>
    </Card>
</template>
