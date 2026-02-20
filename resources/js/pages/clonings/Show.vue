<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import CloningRunController from '@/actions/App/Http/Controllers/CloningRunController';
import RunCard from '@/components/cloning-runs/RunCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import ConnectionTypeIcon from '@/pages/connections/components/ConnectionTypeIcon.vue';
import type { BreadcrumbItem } from '@/types';
import type {
    CloningRun,
    CloningShowProps,
    TriggerConfig,
} from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    Calendar,
    Check,
    Clock,
    Copy,
    Database,
    ExternalLink,
    FileText,
    Globe,
    Hourglass,
    Pause,
    Pencil,
    Play,
    PlayCircle,
    Settings,
    ShieldCheck,
    Trash2,
    Webhook,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<CloningShowProps>();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Clonings',
        href: CloningController.index().url,
    },
    {
        title: props.cloning.title,
        href: CloningController.show(props.cloning.id).url,
    },
]);

const triggerConfig = computed<TriggerConfig | null>(
    () => props.cloning.trigger_config as TriggerConfig | null,
);

const hasWebhooks = computed(() => {
    if (!triggerConfig.value) {
        return false;
    }
    return (
        triggerConfig.value.webhookOnSuccess?.enabled ||
        triggerConfig.value.webhookOnFailure?.enabled
    );
});

const copied = ref(false);

function copyApiUrl() {
    if (props.api_trigger_url) {
        navigator.clipboard.writeText(props.api_trigger_url);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDurationSeconds(totalSeconds: number): string {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }
    return `${seconds}s`;
}

function executeCloning() {
    router.visit(CloningController.execute(props.cloning));
}

function deleteCloning() {
    if (
        confirm(
            `Are you sure you want to delete "${props.cloning.title}"? This cannot be undone.`,
        )
    ) {
        router.visit(CloningController.destroy(props.cloning));
    }
}

function pauseCloning() {
    router.visit(CloningController.pause(props.cloning));
}

function resumeCloning() {
    router.visit(CloningController.resume(props.cloning));
}

// Map runs to include cloning data for RunCard compatibility
const runsWithCloning = computed(() =>
    props.runs.map((run: CloningRun) => ({
        ...run,
        cloning: props.cloning,
    })),
);

const hasFailedRuns = computed(() =>
    props.runs.some((run: CloningRun) => run.status === 'failed'),
);

function deleteFailedRuns() {
    if (
        confirm(
            'Are you sure you want to delete all failed runs for this cloning? This cannot be undone.',
        )
    ) {
        router.delete(CloningController.destroyFailedRuns(props.cloning).url);
    }
}

// PII/GDPR compliance info from anonymization config
interface ColumnMutation {
    columnName: string;
    strategy: string;
    options?: Record<string, unknown>;
}

interface TableConfig {
    tableName: string;
    columnMutations?: ColumnMutation[];
}

const anonymizationStats = computed(() => {
    const config = props.cloning.anonymization_config as {
        tables?: TableConfig[];
    } | null;
    if (!config?.tables) {
        return null;
    }

    const allMutations = config.tables.flatMap((t) => t.columnMutations ?? []);
    const anonymized = allMutations.filter((m) => m.strategy !== 'keep');

    if (anonymized.length === 0) {
        return null;
    }

    const strategies = [...new Set(anonymized.map((m) => m.strategy))].sort();
    const tablesWithTransformations = config.tables.filter((t) =>
        (t.columnMutations ?? []).some((m) => m.strategy !== 'keep'),
    ).length;

    return {
        columnCount: anonymized.length,
        tableCount: tablesWithTransformations,
        strategies: strategies.map(
            (s) => s.charAt(0).toUpperCase() + s.slice(1),
        ),
    };
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="cloning.title" />

        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            {{ cloning.title }}
                        </h1>
                        <Badge variant="outline">
                            <p class="text-muted-foreground">
                                Created {{ formatDate(cloning.created_at) }}
                            </p>
                        </Badge>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="executeCloning"
                        class="gap-2"
                    >
                        <Play class="size-4" />
                        Run Now
                    </Button>

                    <Button variant="outline" size="sm" as-child class="gap-2">
                        <Link :href="`/clonings/${cloning.id}/edit`">
                            <Pencil class="size-4" />
                            Edit
                        </Link>
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        @click="deleteCloning"
                        class="gap-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Configuration Details -->
                <div class="space-y-6 lg:col-span-1">
                    <!-- Connection Info -->
                    <Card
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Database
                                    class="size-4 text-muted-foreground"
                                />
                                Connections
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div>
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                                >
                                    <ConnectionTypeIcon
                                        :type="cloning.source_connection?.type"
                                        size="4"
                                    />
                                    <span class="font-medium text-foreground">
                                        {{
                                            cloning.source_connection?.name ||
                                            'Unknown'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex justify-center text-muted-foreground"
                            >
                                <ArrowRight class="size-4 rotate-90" />
                            </div>

                            <div>
                                <div
                                    class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                                >
                                    <ConnectionTypeIcon
                                        :type="cloning.target_connection?.type"
                                        size="4"
                                    />
                                    <span class="font-medium text-foreground">
                                        {{
                                            cloning.target_connection?.name ||
                                            'Unknown'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Trigger Card -->
                    <Card
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Zap class="size-4 text-muted-foreground" />
                                Triggers
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Schedule trigger section -->
                            <div v-if="cloning.is_scheduled">
                                <div
                                    class="mb-2 flex items-center gap-2 text-sm font-medium text-foreground"
                                >
                                    <Clock class="size-3.5" />
                                    Schedule
                                </div>

                                <!-- Paused state -->
                                <div v-if="cloning.is_paused" class="space-y-2">
                                    <div
                                        class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400"
                                    >
                                        <AlertTriangle class="size-4" />
                                        <span class="text-sm font-medium">
                                            Paused
                                        </span>
                                    </div>
                                    <p
                                        v-if="cloning.consecutive_failures > 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Auto-paused after
                                        {{ cloning.consecutive_failures }}
                                        consecutive failure{{
                                            cloning.consecutive_failures === 1
                                                ? ''
                                                : 's'
                                        }}.
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Cron:
                                        <span class="font-mono">{{
                                            cloning.schedule
                                        }}</span>
                                    </p>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="resumeCloning"
                                        class="w-full gap-2"
                                    >
                                        <PlayCircle class="size-4" />
                                        Resume Schedule
                                    </Button>
                                </div>

                                <!-- Active scheduled state -->
                                <div v-else class="space-y-2">
                                    <div
                                        class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400"
                                    >
                                        <Calendar class="size-4" />
                                        <span class="font-mono text-sm">
                                            {{ cloning.schedule }}
                                        </span>
                                    </div>
                                    <p
                                        v-if="cloning.next_run_at"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Next run:
                                        {{ formatDate(cloning.next_run_at) }}
                                    </p>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="pauseCloning"
                                        class="w-full gap-2"
                                    >
                                        <Pause class="size-4" />
                                        Pause Schedule
                                    </Button>
                                </div>
                            </div>

                            <!-- Divider between sections -->
                            <div
                                v-if="cloning.is_scheduled"
                                class="border-t border-border/60 dark:border-border/40"
                            />

                            <!-- API trigger section -->
                            <div>
                                <div
                                    class="mb-2 flex items-center gap-2 text-sm font-medium text-foreground"
                                >
                                    <Globe class="size-3.5" />
                                    API Trigger
                                </div>

                                <div
                                    v-if="
                                        triggerConfig?.apiTrigger?.enabled &&
                                        api_trigger_url
                                    "
                                >
                                    <div
                                        class="flex items-center gap-2 rounded-lg bg-muted/40 px-3 py-2 dark:bg-muted/20"
                                    >
                                        <code
                                            class="min-w-0 flex-1 truncate text-xs text-foreground"
                                            >{{ api_trigger_url }}</code
                                        >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="size-7 shrink-0 p-0"
                                            @click="copyApiUrl"
                                        >
                                            <Check
                                                v-if="copied"
                                                class="size-3.5 text-emerald-600 dark:text-emerald-400"
                                            />
                                            <Copy
                                                v-else
                                                class="size-3.5 text-muted-foreground"
                                            />
                                        </Button>
                                    </div>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        POST request to trigger a cloning run.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <span class="text-sm">Not configured</span>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div
                                class="border-t border-border/60 dark:border-border/40"
                            />

                            <!-- Manual trigger section -->
                            <div>
                                <div
                                    class="mb-2 flex items-center gap-2 text-sm font-medium text-foreground"
                                >
                                    <Settings class="size-3.5" />
                                    Manual
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Always available via the "Run Now" button.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Webhooks Card -->
                    <Card
                        v-if="hasWebhooks"
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Webhook class="size-4 text-muted-foreground" />
                                Webhooks
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-if="triggerConfig?.webhookOnSuccess?.enabled"
                                class="space-y-1"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"
                                >
                                    <Check class="size-3.5" />
                                    On Success
                                </div>
                                <p
                                    class="truncate pl-5.5 text-xs text-muted-foreground"
                                >
                                    {{ triggerConfig.webhookOnSuccess.url }}
                                </p>
                            </div>
                            <div
                                v-if="triggerConfig?.webhookOnFailure?.enabled"
                                class="space-y-1"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-medium text-red-700 dark:text-red-400"
                                >
                                    <AlertTriangle class="size-3.5" />
                                    On Failure
                                </div>
                                <p
                                    class="truncate pl-5.5 text-xs text-muted-foreground"
                                >
                                    {{ triggerConfig.webhookOnFailure.url }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Estimated Duration -->
                    <Card
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <Hourglass
                                    class="size-4 text-muted-foreground"
                                />
                                Estimated Duration
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="estimatedDuration != null"
                                class="text-2xl font-semibold text-foreground"
                            >
                                {{ formatDurationSeconds(estimatedDuration) }}
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                No completed runs yet
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Audit Log Card -->
                    <Card
                        v-if="lastAuditLogUrl"
                        class="border-border/60 bg-card dark:border-border/40"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <FileText
                                    class="size-4 text-muted-foreground"
                                />
                                Audit Log
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-3 text-sm text-muted-foreground">
                                Public audit log from the latest successful run.
                            </p>
                            <a
                                :href="lastAuditLogUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 text-sm font-medium text-foreground underline-offset-4 hover:underline"
                            >
                                <ExternalLink class="size-3.5" />
                                View Audit Log
                            </a>
                        </CardContent>
                    </Card>

                    <!-- PII/GDPR Compliance -->
                    <Card
                        v-if="anonymizationStats"
                        class="border-emerald-500/20 bg-emerald-500/5 dark:border-emerald-500/15 dark:bg-emerald-500/5"
                    >
                        <CardHeader class="pb-3">
                            <CardTitle
                                class="flex items-center gap-2 text-base font-semibold"
                            >
                                <ShieldCheck
                                    class="size-4 text-emerald-600 dark:text-emerald-400"
                                />
                                PII / GDPR Compliance
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-sm text-muted-foreground">
                                All personally identifiable information is
                                anonymized according to the configured
                                transformation rules.
                            </p>
                            <ul
                                class="space-y-1.5 text-sm text-muted-foreground"
                            >
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="mt-1.5 size-1 shrink-0 rounded-full bg-emerald-500"
                                    />
                                    <span>
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                anonymizationStats.columnCount
                                            }}</span
                                        >
                                        column{{
                                            anonymizationStats.columnCount === 1
                                                ? ''
                                                : 's'
                                        }}
                                        across
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                anonymizationStats.tableCount
                                            }}</span
                                        >
                                        table{{
                                            anonymizationStats.tableCount === 1
                                                ? ''
                                                : 's'
                                        }}
                                        transformed
                                    </span>
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span
                                        class="mt-1.5 size-1 shrink-0 rounded-full bg-emerald-500"
                                    />
                                    <span>
                                        Methods:
                                        <span
                                            class="font-medium text-foreground"
                                            >{{
                                                anonymizationStats.strategies.join(
                                                    ', ',
                                                )
                                            }}</span
                                        >
                                    </span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <!-- Runs List -->
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-lg font-semibold tracking-tight text-foreground"
                        >
                            Recent Runs
                        </h2>
                        <div class="flex items-center gap-3">
                            <Button
                                v-if="hasFailedRuns"
                                variant="outline"
                                size="sm"
                                @click="deleteFailedRuns"
                                class="gap-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <Trash2 class="size-4" />
                                Delete Failed Runs
                            </Button>
                            <Link
                                :href="CloningRunController.index().url"
                                class="text-sm text-muted-foreground hover:text-foreground"
                            >
                                View All
                            </Link>
                        </div>
                    </div>

                    <div
                        v-if="runs.length > 0"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <RunCard
                            v-for="run in runsWithCloning"
                            :key="run.id"
                            :run="run as any"
                            :is-active="
                                ['queued', 'processing'].includes(run.status)
                            "
                        />
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border/60 bg-muted/20 px-6 py-12 text-center dark:border-border/40"
                    >
                        <Play class="mb-4 size-10 text-muted-foreground/50" />
                        <h3 class="mb-1 font-medium text-foreground">
                            No Runs Yet
                        </h3>
                        <p class="mb-4 text-sm text-muted-foreground">
                            This cloning configuration hasn't been executed yet.
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="executeCloning"
                            class="gap-2"
                        >
                            <Play class="size-4" />
                            Run Now
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
