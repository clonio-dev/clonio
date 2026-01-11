<script setup lang="ts">
import RunCard from '@/components/transfer-runs/RunCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import type { DashboardProps } from '@/types/transfer-run.types';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const props = defineProps<DashboardProps>();

const activeRuns = computed(() => {
    return props.runs.filter((run) =>
        ['queued', 'processing'].includes(run.status),
    );
});

const recentRuns = computed(() => {
    if (activeRuns.value.length > 0) {
        return activeRuns.value;
    }
    return props.runs.slice(0, 5);
});

const hasAnyRuns = computed(() => props.runs.length > 0);

let refreshInterval: number | null = null;

function refreshDashboard() {
    router.reload({
        only: ['runs', 'hasActiveRuns'],
        preserveScroll: true,
        preserveState: true,
    });
}

function createFirstConfig() {
    router.visit('/configs/create');
}

onMounted(() => {
    // Only auto-refresh if there are active runs
    if (activeRuns.value.length > 0) {
        refreshInterval = window.setInterval(refreshDashboard, 2000); // Every 2s
    }
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
        <div class="mx-auto max-w-7xl px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Transfer Runs</h1>
                <p class="mt-2 text-gray-600">
                    {{
                        activeRuns.length > 0
                            ? `${activeRuns.length} active run(s)`
                            : 'Recent transfer history'
                    }}
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="!hasAnyRuns" class="py-16 text-center">
                <div
                    class="mb-6 inline-flex h-24 w-24 items-center justify-center rounded-full bg-gray-100"
                >
                    <svg
                        class="h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                </div>

                <h2 class="mb-3 text-2xl font-semibold text-gray-900">
                    No Transfer Runs Yet
                </h2>

                <p class="mx-auto mb-8 max-w-md text-gray-600">
                    Get started by creating your first transfer configuration.
                    You'll be able to anonymize and transfer data between
                    databases in minutes.
                </p>

                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <button
                        @click="createFirstConfig"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Create First Config
                    </button>

                    <a
                        href="/docs/getting-started"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-6 py-3 font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        View Documentation
                    </a>
                </div>

                <!-- Feature Highlights -->
                <div
                    class="mx-auto mt-16 grid max-w-4xl grid-cols-1 gap-8 md:grid-cols-3"
                >
                    <div class="text-center">
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100"
                        >
                            <svg
                                class="h-6 w-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold">Secure Anonymization</h3>
                        <p class="text-sm text-gray-600">
                            GDPR-compliant data anonymization with customizable
                            rules
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-green-100"
                        >
                            <svg
                                class="h-6 w-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold">Fast Transfer</h3>
                        <p class="text-sm text-gray-600">
                            Efficient batch processing with real-time progress
                            tracking
                        </p>
                    </div>

                    <div class="text-center">
                        <div
                            class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100"
                        >
                            <svg
                                class="h-6 w-6 text-purple-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold">Full Audit Trail</h3>
                        <p class="text-sm text-gray-600">
                            Complete logging and compliance documentation
                        </p>
                    </div>
                </div>
            </div>

            <!-- Run List -->
            <div v-else>
                <!-- Active Runs Section -->
                <div v-if="activeRuns.length > 0" class="mb-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Active Runs
                        </h2>
                        <div
                            class="flex items-center gap-2 text-sm text-gray-600"
                        >
                            <div
                                class="h-2 w-2 animate-pulse rounded-full bg-green-500"
                            ></div>
                            <span>Auto-updating every 2s</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <RunCard
                            v-for="run in activeRuns"
                            :key="run.id"
                            :run="run"
                            :is-active="true"
                        />
                    </div>
                </div>

                <!-- Recent Runs Section -->
                <div v-if="activeRuns.length === 0">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">
                        Recent Runs
                    </h2>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <RunCard
                            v-for="run in recentRuns"
                            :key="run.id"
                            :run="run"
                            :is-active="false"
                        />
                    </div>

                    <div class="mt-6 text-center">
                        <a
                            href="/transfer-runs/history"
                            class="inline-flex items-center gap-2 font-medium text-blue-600 hover:text-blue-700"
                        >
                            View Full History
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
