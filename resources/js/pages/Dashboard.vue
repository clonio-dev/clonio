<script setup lang="ts">
import RunCard from '@/components/cloning-runs/RunCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import type { DashboardProps } from '@/types/cloning.types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    CheckCircle2,
    Clock,
    Database,
    FileText,
    Play,
    Plus,
    RefreshCw,
    Shield,
    XCircle,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const props = defineProps<DashboardProps>();

const isRefreshing = ref(false);

const hasAnyRuns = computed(() => props.recentRuns.length > 0);

let refreshInterval: number | null = null;

function refreshDashboard() {
    isRefreshing.value = true;
    router.reload({
        only: ['recentRuns', 'activeRuns', 'totalRuns'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
}

function createFirstCloning() {
    router.visit('/clonings/create');
}

function setupAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    if (props.activeRuns.length > 0) {
        refreshInterval = window.setInterval(refreshDashboard, 1000);
    }
}

watch(
    () => props.activeRuns.length,
    () => {
        setupAutoRefresh();
    },
);

onMounted(() => {
    setupAutoRefresh();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500/20 to-purple-500/20 ring-1 ring-violet-500/30 dark:from-violet-500/10 dark:to-purple-500/10"
                        >
                            <Activity
                                class="size-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            Dashboard
                        </h1>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Monitor your data transfer runs and system activity
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Auto-refresh indicator -->
                    <div
                        v-if="props.activeRuns.length > 0"
                        class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400"
                    >
                        <div
                            class="size-2 animate-pulse rounded-full bg-emerald-500"
                        />
                        <span>Live</span>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        @click="refreshDashboard"
                        :disabled="isRefreshing || props.activeRuns.length > 0"
                        class="gap-2"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': isRefreshing }"
                        />
                        Refresh
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div
                v-if="hasAnyRuns"
                class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <!-- Active Runs -->
                <Card
                    class="relative overflow-hidden border-border/60 dark:border-border/40"
                >
                    <div
                        v-if="props.activeRuns.length > 0"
                        class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-teal-500/5"
                    />
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Active Runs
                        </CardTitle>
                        <div
                            class="flex size-8 items-center justify-center rounded-lg"
                            :class="
                                props.activeRuns.length > 0
                                    ? 'bg-emerald-100 dark:bg-emerald-950/50'
                                    : 'bg-muted'
                            "
                        >
                            <Play
                                class="size-4"
                                :class="
                                    props.activeRuns.length > 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-muted-foreground'
                                "
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ props.activeRuns.length }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                props.activeRuns.length > 0
                                    ? 'Currently processing'
                                    : 'No active transfers'
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Completed -->
                <Card class="border-border/60 dark:border-border/40">
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Completed
                        </CardTitle>
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950/50"
                        >
                            <CheckCircle2
                                class="size-4 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ props.completedRuns }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Successful transfers
                        </p>
                    </CardContent>
                </Card>

                <!-- Failed -->
                <Card class="border-border/60 dark:border-border/40">
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Failed
                        </CardTitle>
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/50"
                        >
                            <XCircle
                                class="size-4 text-red-600 dark:text-red-400"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ props.failedRuns }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Requires attention
                        </p>
                    </CardContent>
                </Card>

                <!-- Total -->
                <Card class="border-border/60 dark:border-border/40">
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total Runs
                        </CardTitle>
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-950/50"
                        >
                            <Clock
                                class="size-4 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ props.totalRuns }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            All time transfers
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-if="!hasAnyRuns"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/60 bg-gradient-to-b from-muted/20 to-muted/40 px-6 py-20 text-center dark:border-border/40 dark:from-muted/10 dark:to-muted/20"
            >
                <div
                    class="mb-6 flex size-20 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/20 to-cyan-500/10 ring-1 ring-cyan-500/20"
                >
                    <Database
                        class="size-10 text-cyan-600 dark:text-cyan-400"
                    />
                </div>

                <h2
                    class="mb-2 text-xl font-semibold tracking-tight text-foreground"
                >
                    No Cloning Runs Yet
                </h2>

                <p class="mx-auto mb-8 max-w-md text-sm text-muted-foreground">
                    Get started by creating your first cloning configuration.
                    You'll be able to anonymize and transfer data between
                    databases securely.
                </p>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        @click="createFirstCloning"
                        class="gap-2 bg-gradient-to-r from-violet-600 to-purple-600 text-white shadow-md shadow-violet-500/20 hover:from-violet-500 hover:to-purple-500"
                    >
                        <Plus class="size-4" />
                        Create First Cloning
                    </Button>

                    <Button
                        variant="outline"
                        as="a"
                        href="/docs/getting-started"
                        class="gap-2"
                    >
                        <FileText class="size-4" />
                        View Documentation
                    </Button>
                </div>

                <!-- Feature Highlights -->
                <div
                    class="mt-16 grid max-w-3xl grid-cols-1 gap-8 sm:grid-cols-3"
                >
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="mb-4 flex size-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/50"
                        >
                            <Shield
                                class="size-6 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <h3 class="mb-1 font-medium text-foreground">
                            Secure Anonymization
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            GDPR-compliant data anonymization with customizable
                            rules
                        </p>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div
                            class="mb-4 flex size-12 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/50"
                        >
                            <Zap
                                class="size-6 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                        <h3 class="mb-1 font-medium text-foreground">
                            Fast Transfer
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Efficient batch processing with real-time progress
                            tracking
                        </p>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div
                            class="mb-4 flex size-12 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/50"
                        >
                            <CheckCircle2
                                class="size-6 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                        <h3 class="mb-1 font-medium text-foreground">
                            Full Audit Trail
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Complete logging and compliance documentation
                        </p>
                    </div>
                </div>
            </div>

            <!-- Run List -->
            <div v-else class="space-y-6">
                <!-- Active Runs Section -->
                <div v-if="props.activeRuns.length > 0">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2
                                class="text-lg font-semibold tracking-tight text-foreground"
                            >
                                Active Runs
                            </h2>
                            <div
                                class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400"
                            >
                                <div
                                    class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                />
                                {{ props.activeRuns.length }} running
                            </div>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Auto-refreshing every 3s
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <RunCard
                            v-for="run in props.recentRuns"
                            :key="run.id"
                            :run="run"
                            :is-active="true"
                        />
                    </div>
                </div>

                <!-- Recent Runs Section -->
                <div v-if="props.activeRuns.length === 0">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-lg font-semibold tracking-tight text-foreground"
                        >
                            Recent Runs
                        </h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <RunCard
                            v-for="run in props.recentRuns"
                            :key="run.id"
                            :run="run"
                            :is-active="false"
                        />
                    </div>

                    <div class="mt-6 flex justify-center">
                        <Button
                            variant="ghost"
                            as-child
                            class="gap-2 text-muted-foreground hover:text-foreground"
                        >
                            <Link href="/cloning-runs">
                                View Full History
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
