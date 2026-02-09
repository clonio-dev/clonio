<script setup lang="ts">
import type { DocChapter } from '@/types/docs';
import { Link } from '@inertiajs/vue3';

interface Props {
    chapters: DocChapter[];
    currentChapter: string;
    currentPage: string;
}

const props = defineProps<Props>();

function isActivePage(chapterSlug: string, pageSlug: string): boolean {
    return (
        props.currentChapter === chapterSlug && props.currentPage === pageSlug
    );
}
</script>

<template>
    <nav class="space-y-6" aria-label="Documentation navigation">
        <div v-for="chapter in chapters" :key="chapter.slug">
            <h3
                class="mb-2 px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                {{ chapter.title }}
            </h3>
            <ul class="space-y-0.5">
                <li v-for="page in chapter.pages" :key="page.slug">
                    <Link
                        :href="`/docs/${chapter.slug}/${page.slug}`"
                        class="block rounded-md px-3 py-1.5 text-sm transition-colors"
                        :class="
                            isActivePage(chapter.slug, page.slug)
                                ? 'bg-primary/10 font-semibold text-primary dark:bg-primary/20 dark:text-primary'
                                : 'text-foreground/70 hover:bg-accent hover:text-foreground dark:text-foreground/60 dark:hover:bg-accent/50 dark:hover:text-foreground'
                        "
                    >
                        {{ page.title }}
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>
