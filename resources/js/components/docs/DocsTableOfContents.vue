<script setup lang="ts">
import type { DocHeading } from '@/types/docs';
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    headings: DocHeading[];
}

const props = defineProps<Props>();

const activeSlug = ref<string>('');

let observer: IntersectionObserver | null = null;

onMounted(() => {
    const headingElements = props.headings
        .map((h) => document.getElementById(h.slug))
        .filter(Boolean) as HTMLElement[];

    if (headingElements.length === 0) {
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    activeSlug.value = entry.target.id;
                    break;
                }
            }
        },
        { rootMargin: '-80px 0px -70% 0px', threshold: 0 },
    );

    for (const el of headingElements) {
        observer.observe(el);
    }
});

onBeforeUnmount(() => {
    observer?.disconnect();
});
</script>

<template>
    <nav
        v-if="headings.length > 0"
        class="sticky top-20"
        aria-label="Table of contents"
    >
        <h4
            class="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
        >
            On this page
        </h4>
        <ul class="space-y-1 border-l border-border">
            <li v-for="heading in headings" :key="heading.slug">
                <a
                    :href="`#${heading.slug}`"
                    class="block border-l-2 py-1 text-sm transition-colors"
                    :class="[
                        heading.level === 3 ? 'pl-6' : 'pl-3',
                        activeSlug === heading.slug
                            ? 'border-primary font-medium text-primary dark:border-primary dark:text-primary'
                            : '-ml-px border-transparent text-muted-foreground hover:border-foreground/30 hover:text-foreground dark:hover:text-foreground',
                    ]"
                >
                    {{ heading.text }}
                </a>
            </li>
        </ul>
    </nav>
</template>
