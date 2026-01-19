<script setup lang="ts">
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Connection } from '@/pages/connections/types';
import { router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Cable,
    Copy,
    Database,
    MoreVertical,
    Pencil,
    Server,
    Trash2,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    connection: Connection;
}

const props = defineProps<Props>();

const copied = ref(false);

const databaseTypeConfig = computed(() => {
    const configs: Record<
        string,
        { label: string; color: string; gradient: string }
    > = {
        mysql: {
            label: 'MySQL',
            color: 'text-orange-600 dark:text-orange-400',
            gradient: 'from-orange-500/20 to-amber-500/20',
        },
        pgsql: {
            label: 'PostgreSQL',
            color: 'text-blue-600 dark:text-blue-400',
            gradient: 'from-blue-500/20 to-indigo-500/20',
        },
        sqlserver: {
            label: 'SQL Server',
            color: 'text-red-600 dark:text-red-400',
            gradient: 'from-red-500/20 to-rose-500/20',
        },
    };
    return (
        configs[props.connection.type] || {
            label: props.connection.type,
            color: 'text-gray-600 dark:text-gray-400',
            gradient: 'from-gray-500/20 to-slate-500/20',
        }
    );
});

const connectionString = computed(() => {
    return `${props.connection.host}:${props.connection.port}/${props.connection.database}`;
});

function copyConnectionString() {
    navigator.clipboard.writeText(connectionString.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function deleteConnection() {
    if (confirm('Are you sure you want to delete this connection?')) {
        router.delete(`/connections/${props.connection.id}`);
    }
}
</script>

<template>
    <Card
        class="group relative overflow-hidden border-border/50 bg-gradient-to-br from-card to-card/80 transition-all duration-300 hover:border-border hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/20"
    >
        <!-- Decorative gradient line at top -->
        <div
            class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            :class="databaseTypeConfig.gradient.replace('/20', '')"
        ></div>

        <!-- Production badge -->
        <div
            v-if="connection.is_production_stage"
            class="absolute right-3 top-3 z-10"
        >
            <TooltipProvider>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Badge
                            variant="destructive"
                            class="gap-1 bg-amber-500/90 text-amber-950 hover:bg-amber-500 dark:bg-amber-500/80 dark:text-amber-950"
                        >
                            <AlertTriangle class="h-3 w-3" />
                            PROD
                        </Badge>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>Production environment - handle with care</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </div>

        <CardHeader class="pb-3">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <!-- Database type icon -->
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br ring-1 ring-black/5 transition-transform duration-300 group-hover:scale-105 dark:ring-white/10"
                        :class="databaseTypeConfig.gradient"
                    >
                        <Database class="h-5 w-5" :class="databaseTypeConfig.color" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <CardTitle
                            class="truncate text-base font-semibold text-foreground"
                        >
                            {{ connection.name }}
                        </CardTitle>
                        <CardDescription class="mt-0.5 flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1 text-xs font-medium"
                                :class="databaseTypeConfig.color"
                            >
                                {{ databaseTypeConfig.label }}
                            </span>
                        </CardDescription>
                    </div>
                </div>

                <!-- Actions dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 data-[state=open]:opacity-100"
                        >
                            <MoreVertical class="h-4 w-4" />
                            <span class="sr-only">Open menu</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem class="gap-2">
                            <Pencil class="h-4 w-4" />
                            Edit connection
                        </DropdownMenuItem>
                        <DropdownMenuItem class="gap-2" @click="copyConnectionString">
                            <Copy class="h-4 w-4" />
                            Copy connection string
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            class="gap-2 text-destructive focus:bg-destructive/10 focus:text-destructive"
                            @click="deleteConnection"
                        >
                            <Trash2 class="h-4 w-4" />
                            Delete connection
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 pt-0">
            <!-- Connection details -->
            <div class="space-y-2">
                <!-- Host & Port -->
                <div
                    class="flex items-center gap-2 rounded-md bg-muted/50 px-2.5 py-1.5 text-sm transition-colors hover:bg-muted"
                >
                    <Server class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                    <span class="truncate font-mono text-xs text-foreground">
                        {{ connection.host }}:{{ connection.port }}
                    </span>
                </div>

                <!-- Database -->
                <div
                    class="flex items-center gap-2 rounded-md bg-muted/50 px-2.5 py-1.5 text-sm transition-colors hover:bg-muted"
                >
                    <Cable class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                    <span class="truncate font-mono text-xs text-foreground">
                        {{ connection.database }}
                    </span>
                </div>

                <!-- Username -->
                <div
                    class="flex items-center gap-2 rounded-md bg-muted/50 px-2.5 py-1.5 text-sm transition-colors hover:bg-muted"
                >
                    <User class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                    <span class="truncate font-mono text-xs text-foreground">
                        {{ connection.username }}
                    </span>
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
                    class="flex items-center justify-center gap-1.5 rounded-md bg-emerald-500/10 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                >
                    <Copy class="h-3 w-3" />
                    Copied to clipboard
                </div>
            </Transition>
        </CardContent>
    </Card>
</template>
