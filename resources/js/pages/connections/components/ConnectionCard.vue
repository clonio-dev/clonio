<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ConnectionTypeIcon from '@/pages/connections/components/ConnectionTypeIcon.vue';
import type { Connection } from '@/pages/connections/types';
import { router } from '@inertiajs/vue3';
import {
    Check,
    Database,
    Pencil,
    RotateCw,
    Server,
    Trash2,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    connection: Connection;
}

interface Emits {
    (e: 'edit', connection: Connection): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const copied = ref(false);

interface DatabaseTypeConfig {
    label: string;
}

const databaseTypeConfigs: Record<string, DatabaseTypeConfig> = {
    mysql: {
        label: 'MySQL',
    },
    mariadb: {
        label: 'MariaDB',
    },
    pgsql: {
        label: 'PostgreSQL',
    },
    sqlserver: {
        label: 'SQL Server',
    },
    sqlite: {
        label: 'SQLite',
    },
};

const databaseTypeConfig = computed(() => {
    return (
        databaseTypeConfigs[props.connection.type] || {
            label: props.connection.type,
        }
    );
});

const deleteConnection = () => {
    if (confirm('Are you sure you want to delete this connection?')) {
        router.delete(
            DatabaseConnectionController.destroy(props.connection.id).url,
        );
    }
};
</script>

<template>
    <Card
        class="group relative overflow-hidden border-border/60 bg-card transition-all duration-300 hover:border-border hover:shadow-lg hover:shadow-black/5 dark:border-border dark:hover:shadow-black/20"
    >
        <CardHeader>
            <div class="flex items-start gap-4">
                <!-- Database icon -->
                <ConnectionTypeIcon :type="props.connection.type" size="10" />

                <div class="min-w-0 flex-1 space-y-1.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <CardTitle
                                class="truncate text-lg leading-tight font-semibold text-foreground"
                            >
                                {{ connection.name }}
                            </CardTitle>
                            <CardDescription
                                class="mt-1 flex items-center gap-2"
                            >
                                <p
                                    class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                                >
                                    {{ databaseTypeConfig.label }}
                                    {{ connection.version }}
                                </p>
                            </CardDescription>
                        </div>
                        <div>
                            <Badge
                                v-if="connection.is_production_stage"
                                variant="outline"
                                class="bg-amber-100 text-xs font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-400"
                            >
                                Source
                            </Badge>
                        </div>
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
                    <Server class="size-4 shrink-0 text-muted-foreground/70" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-mono text-sm text-foreground">
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
                        <p class="truncate font-mono text-sm text-foreground">
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
                        <p class="truncate font-mono text-sm text-foreground">
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
        <CardFooter class="justify-between">
            <div class="flex items-center gap-2">
                <div
                    class="size-2 rounded-full"
                    :class="{
                        'bg-slate-400': !props.connection.last_tested_at,
                        'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]':
                            props.connection.last_tested_at &&
                            props.connection.is_connectable,
                        'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]':
                            props.connection.last_tested_at &&
                            !props.connection.is_connectable,
                    }"
                ></div>
                <div class="flex flex-col">
                    <span
                        class="text-xs font-bold"
                        :class="{
                            'text-slate-500': !props.connection.last_tested_at,
                            'text-emerald-600':
                                props.connection.last_tested_at &&
                                props.connection.is_connectable,
                            'text-red-600':
                                props.connection.last_tested_at &&
                                !props.connection.is_connectable,
                        }"
                        >{{ props.connection.last_test_result }}</span
                    >
                    <span class="text-[10px] text-slate-400">{{
                        props.connection.last_tested_at_label
                    }}</span>
                </div>
            </div>
            <div class="flex gap-1">
                <Button
                    variant="ghost"
                    title="Edit"
                    class="text-accent-foreground/50 group-hover:text-accent-foreground"
                    @click="emit('edit', props.connection)"
                >
                    <span class="sr-only">edit</span>
                    <Pencil />
                </Button>
                <Button
                    variant="ghost"
                    title="Refresh"
                    class="text-accent-foreground/50 group-hover:text-accent-foreground"
                    @click="
                        router.post(
                            DatabaseConnectionController.testConnection(
                                props.connection,
                            ).url,
                        )
                    "
                >
                    <span class="sr-only">refresh</span>
                    <RotateCw />
                </Button>
                <Button
                    variant="ghost"
                    @click="deleteConnection"
                    title="Delete"
                    class="group/button"
                >
                    <span class="sr-only">delete</span>
                    <Trash2
                        class="text-slate-400 group-hover:text-red-400 group-hover/button:text-red-600 dark:group-hover/button:text-red-400"
                    />
                </Button>
            </div>
        </CardFooter>
    </Card>
</template>
