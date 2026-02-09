<script setup lang="ts">
import DocsLayout from '@/layouts/docs/DocsLayout.vue';
import type { DocSearchProps } from '@/types/docs';
import { Head, Link } from '@inertiajs/vue3';
import { FileText, SearchX } from 'lucide-vue-next';

defineProps<DocSearchProps>();
</script>

<template>
    <Head :title="`Search: ${query} - Documentation`" />

    <DocsLayout :chapters="chapters">
        <div class="max-w-2xl">
            <header class="mb-8">
                <h1
                    class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                >
                    Search results
                </h1>
                <p v-if="query" class="mt-2 text-muted-foreground">
                    {{ results.length }} result{{
                        results.length !== 1 ? 's' : ''
                    }}
                    for "{{ query }}"
                </p>
            </header>

            <!-- Results -->
            <div v-if="results.length > 0" class="space-y-4">
                <Link
                    v-for="result in results"
                    :key="result.url"
                    :href="result.url"
                    class="block rounded-lg border border-border bg-card p-4 transition-colors hover:border-primary/30 hover:bg-accent/50"
                >
                    <div class="flex items-start gap-3">
                        <FileText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0">
                            <h2 class="font-medium text-foreground">
                                {{ result.title }}
                            </h2>
                            <p class="mt-0.5 text-xs text-primary/80">
                                {{ result.chapterTitle }}
                            </p>
                            <p
                                v-if="result.introduction"
                                class="mt-1.5 line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ result.introduction }}
                            </p>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="query"
                class="flex flex-col items-center py-16 text-center"
            >
                <SearchX class="mb-4 size-12 text-muted-foreground/50" />
                <h2 class="text-lg font-medium text-foreground">
                    No results found
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try adjusting your search terms or browse the chapters in
                    the sidebar.
                </p>
            </div>
        </div>
    </DocsLayout>
</template>
