<script setup lang="ts">
import DocsLayout from '@/layouts/docs/DocsLayout.vue';
import type { DocShowProps } from '@/types/docs';
import { Head, Link } from '@inertiajs/vue3';
import hljs from 'highlight.js';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import { nextTick, onMounted, watch } from 'vue';

const props = defineProps<DocShowProps>();

function highlightCode() {
    nextTick(() => {
        document
            .querySelectorAll('.docs-content pre code:not(.hljs)')
            .forEach((block) => {
                hljs.highlightElement(block as HTMLElement);
            });
    });
}

onMounted(highlightCode);

watch(() => props.page.htmlContent, highlightCode);
</script>

<template>
    <Head :title="`${page.title} - Documentation`" />

    <DocsLayout
        :chapters="chapters"
        :current-chapter="currentChapter"
        :current-page="currentPage"
        :headings="page.headings"
    >
        <article class="docs-content">
            <header class="mb-8">
                <h1
                    class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                >
                    {{ page.title }}
                </h1>
                <p
                    v-if="page.introduction"
                    class="mt-3 text-lg text-muted-foreground"
                >
                    {{ page.introduction }}
                </p>
            </header>

            <div
                class="prose prose-slate dark:prose-invert prose-headings:scroll-mt-20 prose-headings:font-semibold prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-code:rounded prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:text-sm prose-code:font-normal prose-code:before:content-none prose-code:after:content-none prose-pre:bg-slate-900 prose-pre:dark:bg-slate-950 prose-img:rounded-lg max-w-none"
                v-html="page.htmlContent"
            />

            <!-- Previous / Next navigation -->
            <nav
                v-if="page.previousPage || page.nextPage"
                class="mt-12 flex items-stretch border-t border-border pt-6"
            >
                <Link
                    v-if="page.previousPage"
                    :href="page.previousPage.url"
                    class="group flex flex-1 items-center gap-3 rounded-lg border border-border px-4 py-3 transition-colors hover:bg-accent"
                >
                    <ArrowLeft
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:-translate-x-0.5"
                    />
                    <div class="min-w-0">
                        <div class="text-xs text-muted-foreground sr-only">
                            Previous
                        </div>
                        <div
                            class="truncate text-sm font-medium text-foreground"
                        >
                            {{ page.previousPage.title }}
                        </div>
                    </div>
                </Link>

                <div v-else class="flex-1" />

                <Link
                    v-if="page.nextPage"
                    :href="page.nextPage.url"
                    class="group flex flex-1 items-center justify-end gap-3 rounded-lg border border-border px-4 py-3 transition-colors hover:bg-accent"
                    :class="{ 'ml-3': page.previousPage }"
                >
                    <div class="min-w-0 text-right">
                        <div class="text-xs text-muted-foreground sr-only">Next</div>
                        <div
                            class="truncate text-sm font-medium text-foreground"
                        >
                            {{ page.nextPage.title }}
                        </div>
                    </div>
                    <ArrowRight
                        class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </nav>
        </article>
    </DocsLayout>
</template>

<style>
/* highlight.js light theme */
@import 'highlight.js/styles/github.css';

/* Override with dark theme in dark mode */
.dark .hljs {
    color: #c9d1d9;
    background: #0d1117;
}

.dark .hljs-doctag,
.dark .hljs-keyword,
.dark .hljs-meta .hljs-keyword,
.dark .hljs-template-tag,
.dark .hljs-template-variable,
.dark .hljs-type,
.dark .hljs-variable.language_ {
    color: #ff7b72;
}

.dark .hljs-title,
.dark .hljs-title.class_,
.dark .hljs-title.class_.inherited__,
.dark .hljs-title.function_ {
    color: #d2a8ff;
}

.dark .hljs-attr,
.dark .hljs-attribute,
.dark .hljs-literal,
.dark .hljs-meta,
.dark .hljs-number,
.dark .hljs-operator,
.dark .hljs-variable,
.dark .hljs-selector-attr,
.dark .hljs-selector-class,
.dark .hljs-selector-id {
    color: #79c0ff;
}

.dark .hljs-regexp,
.dark .hljs-string,
.dark .hljs-meta .hljs-string {
    color: #a5d6ff;
}

.dark .hljs-built_in,
.dark .hljs-symbol {
    color: #ffa657;
}

.dark .hljs-comment,
.dark .hljs-code,
.dark .hljs-formula {
    color: #8b949e;
}

.dark .hljs-name,
.dark .hljs-quote,
.dark .hljs-selector-tag,
.dark .hljs-selector-pseudo {
    color: #7ee787;
}

.dark .hljs-subst {
    color: #c9d1d9;
}

.dark .hljs-section {
    color: #1f6feb;
    font-weight: bold;
}

.dark .hljs-bullet {
    color: #f2cc60;
}

.dark .hljs-emphasis {
    color: #c9d1d9;
    font-style: italic;
}

.dark .hljs-strong {
    color: #c9d1d9;
    font-weight: bold;
}

.dark .hljs-addition {
    color: #aff5b4;
    background-color: #033a16;
}

.dark .hljs-deletion {
    color: #ffdcd7;
    background-color: #67060c;
}
</style>
