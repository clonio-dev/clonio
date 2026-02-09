<script setup lang="ts">
import DocsLayout from '@/layouts/docs/DocsLayout.vue';
import type { DocShowProps } from '@/types/docs';
import { Head, Link } from '@inertiajs/vue3';
import hljs from 'highlight.js';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<DocShowProps>();

const copiedSlug = ref<string | null>(null);
let copiedTimeout: ReturnType<typeof setTimeout> | null = null;

function enhanceContent() {
    nextTick(() => {
        document
            .querySelectorAll('.docs-content pre code:not(.hljs)')
            .forEach((block) => {
                hljs.highlightElement(block as HTMLElement);
            });

        document
            .querySelectorAll('.docs-content h2[id], .docs-content h3[id]')
            .forEach((heading) => {
                if (heading.querySelector('.heading-anchor')) {
                    return;
                }

                heading.classList.add('heading-linkable');

                const anchor = document.createElement('a');
                anchor.href = `#${heading.id}`;
                anchor.className = 'heading-anchor';
                anchor.setAttribute('aria-label', 'Copy link to section');
                anchor.setAttribute('title', 'Copy link to section');
                anchor.innerHTML =
                    '<svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>';

                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url =
                        window.location.origin +
                        window.location.pathname +
                        `#${heading.id}`;
                    navigator.clipboard.writeText(url);
                    history.replaceState(null, '', `#${heading.id}`);

                    copiedSlug.value = heading.id;
                    if (copiedTimeout) {
                        clearTimeout(copiedTimeout);
                    }
                    copiedTimeout = setTimeout(
                        () => (copiedSlug.value = null),
                        2000,
                    );
                });

                heading.appendChild(anchor);
            });
    });
}

onMounted(enhanceContent);

watch(() => props.page.htmlContent, enhanceContent);
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
                class="prose max-w-none prose-slate dark:prose-invert prose-headings:scroll-mt-20 prose-headings:font-semibold prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-code:rounded prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:text-sm prose-code:font-normal prose-code:before:content-none prose-code:after:content-none prose-pre:bg-slate-900 prose-pre:dark:bg-slate-950 prose-img:rounded-lg"
                v-html="page.htmlContent"
            />

            <!-- Copied toast -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="copiedSlug"
                    class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground shadow-lg"
                >
                    Link copied to clipboard
                </div>
            </Transition>

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
                        <div class="sr-only text-xs text-muted-foreground">
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
                        <div class="sr-only text-xs text-muted-foreground">
                            Next
                        </div>
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
/* Heading anchor links */
.heading-linkable {
    position: relative;
    cursor: pointer;
}

.heading-anchor {
    display: inline-flex;
    align-items: center;
    margin-left: 0.4rem;
    color: var(--muted-foreground);
    opacity: 0;
    transition: opacity 0.15s ease;
    vertical-align: middle;
}

.heading-linkable:hover .heading-anchor {
    opacity: 1;
}

.heading-anchor:hover {
    color: var(--primary);
}

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
