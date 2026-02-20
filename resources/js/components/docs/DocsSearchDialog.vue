<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { DocSearchResult } from '@/types/docs';
import { router } from '@inertiajs/vue3';
import { FileText, Loader2, Search } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const isOpen = ref(false);
const query = ref('');
const results = ref<DocSearchResult[]>([]);
const isLoading = ref(false);
const selectedIndex = ref(-1);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

function open() {
    isOpen.value = true;
    query.value = '';
    results.value = [];
    selectedIndex.value = -1;
}

function close() {
    isOpen.value = false;
}

function navigateTo(url: string) {
    close();
    router.visit(url);
}

async function fetchResults(searchQuery: string) {
    if (abortController) {
        abortController.abort();
    }

    const trimmed = searchQuery.trim();
    if (!trimmed) {
        results.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    abortController = new AbortController();

    try {
        const response = await fetch(
            `/docs/search/json?q=${encodeURIComponent(trimmed)}`,
            { signal: abortController.signal },
        );
        const data = await response.json();
        results.value = data.results;
        selectedIndex.value = -1;
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            return;
        }
        results.value = [];
    } finally {
        isLoading.value = false;
    }
}

watch(query, (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    if (!value.trim()) {
        results.value = [];
        isLoading.value = false;
        return;
    }
    isLoading.value = true;
    debounceTimer = setTimeout(() => fetchResults(value), 250);
});

function handleResultKeydown(event: KeyboardEvent) {
    if (!isOpen.value) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (results.value.length > 0) {
            selectedIndex.value =
                (selectedIndex.value + 1) % results.value.length;
            scrollToSelected();
        }
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (results.value.length > 0) {
            selectedIndex.value =
                selectedIndex.value <= 0
                    ? results.value.length - 1
                    : selectedIndex.value - 1;
            scrollToSelected();
        }
    } else if (event.key === 'Enter') {
        if (
            selectedIndex.value >= 0 &&
            selectedIndex.value < results.value.length
        ) {
            event.preventDefault();
            navigateTo(results.value[selectedIndex.value].url);
        }
    }
}

function scrollToSelected() {
    nextTick(() => {
        const el = document.querySelector(
            `[data-result-index="${selectedIndex.value}"]`,
        );
        el?.scrollIntoView({ block: 'nearest' });
    });
}

function handleKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        if (isOpen.value) {
            close();
        } else {
            open();
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    if (abortController) {
        abortController.abort();
    }
});

defineExpose({ open });
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent
            class="top-[20%] max-w-xl translate-y-0 gap-0 overflow-hidden p-0"
            :show-close-button="false"
        >
            <DialogTitle class="sr-only">Search documentation</DialogTitle>
            <DialogDescription class="sr-only">
                Type to search through documentation pages
            </DialogDescription>
            <div class="flex items-center border-b border-border px-4">
                <Search class="mr-2 size-4 shrink-0 text-muted-foreground" />
                <input
                    v-model="query"
                    type="text"
                    placeholder="Search documentation..."
                    class="h-12 w-full bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
                    @keydown="handleResultKeydown"
                />
            </div>

            <!-- Results area -->
            <div class="max-h-80 overflow-y-auto">
                <!-- Loading state -->
                <div
                    v-if="isLoading"
                    class="flex items-center justify-center px-4 py-8"
                >
                    <Loader2
                        class="size-5 animate-spin text-muted-foreground"
                    />
                </div>

                <!-- Results list -->
                <div v-else-if="results.length > 0" class="py-2">
                    <button
                        v-for="(result, index) in results"
                        :key="result.url"
                        :data-result-index="index"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-accent"
                        :class="{
                            'bg-accent': selectedIndex === index,
                        }"
                        @click="navigateTo(result.url)"
                        @mouseenter="selectedIndex = index"
                    >
                        <FileText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-foreground">
                                {{ result.title }}
                            </div>
                            <div
                                v-if="result.introduction"
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                {{ result.introduction }}
                            </div>
                            <div
                                v-if="result.chapterTitle"
                                class="mt-1 text-xs text-muted-foreground/70"
                            >
                                {{ result.chapterTitle }}
                            </div>
                        </div>
                    </button>
                </div>

                <!-- No results -->
                <div
                    v-else-if="query.trim()"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No results found
                </div>

                <!-- Default state -->
                <div
                    v-else
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    Type to search documentation...
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
