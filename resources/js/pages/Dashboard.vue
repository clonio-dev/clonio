<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import type { DashboardProps, TransferRun } from '@/types/transfer-run.types';
import RunCard from '@/components/transfer-runs/RunCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const props = defineProps<DashboardProps>();

const activeRuns = computed(() => {
    return props.runs.filter(run => ['queued', 'processing'].includes(run.status));
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
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Transfer Runs</h1>
                <p class="text-gray-600 mt-2">
                    {{ activeRuns.length > 0 ? `${activeRuns.length} active run(s)` : 'Recent transfer history' }}
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="!hasAnyRuns" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>

                <h2 class="text-2xl font-semibold text-gray-900 mb-3">
                    No Transfer Runs Yet
                </h2>

                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Get started by creating your first transfer configuration.
                    You'll be able to anonymize and transfer data between databases in minutes.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button
                        @click="createFirstConfig"
                        class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create First Config
                    </button>

                    <a
                        href="/docs/getting-started"
                        class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors inline-flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        View Documentation
                    </a>
                </div>

                <!-- Feature Highlights -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold mb-2">Secure Anonymization</h3>
                        <p class="text-sm text-gray-600">GDPR-compliant data anonymization with customizable rules</p>
                    </div>

                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold mb-2">Fast Transfer</h3>
                        <p class="text-sm text-gray-600">Efficient batch processing with real-time progress tracking</p>
                    </div>

                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold mb-2">Full Audit Trail</h3>
                        <p class="text-sm text-gray-600">Complete logging and compliance documentation</p>
                    </div>
                </div>
            </div>

            <!-- Run List -->
            <div v-else>
                <!-- Active Runs Section -->
                <div v-if="activeRuns.length > 0" class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Active Runs
                        </h2>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span>Auto-updating every 2s</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Recent Runs
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <RunCard
                            v-for="run in recentRuns"
                            :key="run.id"
                            :run="run"
                            :is-active="false"
                        />
                    </div>

                    <div class="text-center mt-6">
                        <a
                            href="/transfer-runs/history"
                            class="text-blue-600 hover:text-blue-700 font-medium inline-flex items-center gap-2"
                        >
                            View Full History
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
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
