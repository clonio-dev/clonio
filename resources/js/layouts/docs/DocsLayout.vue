<script setup lang="ts">
import DocsSearchDialog from '@/components/docs/DocsSearchDialog.vue';
import DocsSidebar from '@/components/docs/DocsSidebar.vue';
import DocsTableOfContents from '@/components/docs/DocsTableOfContents.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import type { DocChapter, DocHeading } from '@/types/docs';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Menu, Moon, Sun, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    chapters: DocChapter[];
    currentChapter?: string;
    currentPage?: string;
    headings?: DocHeading[];
}

withDefaults(defineProps<Props>(), {
    currentChapter: '',
    currentPage: '',
    headings: () => [],
});

const { appearance, updateAppearance } = useAppearance();
const isMobileMenuOpen = ref(false);
const searchDialog = ref<InstanceType<typeof DocsSearchDialog> | null>(null);

function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    updateAppearance(isDark ? 'light' : 'dark');
}

function openSearch() {
    searchDialog.value?.open();
}

const modifierKey = computed(() => {
    if (typeof window === 'undefined') {
        return 'Ctrl';
    }
    return window.navigator?.platform?.includes('Mac') ? '\u2318' : 'Ctrl';
});
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <!-- Header -->
        <header
            class="sticky top-0 z-40 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div class="mx-auto flex h-14 max-w-7xl items-center px-4 lg:px-6">
                <!-- Mobile menu button -->
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="mr-2 md:hidden"
                    aria-label="Toggle navigation menu"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                >
                    <X v-if="isMobileMenuOpen" class="size-5" />
                    <Menu v-else class="size-5" />
                </Button>

                <!-- Logo -->
                <Link
                    href="/docs"
                    class="flex items-center gap-2 font-semibold text-foreground"
                >
                    <BookOpen class="size-5 text-primary" />
                    <span>Documentation</span>
                </Link>

                <div class="flex-1" />

                <!-- Search -->
                <button
                    class="mr-2 flex h-8 w-48 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm text-muted-foreground transition-colors hover:bg-accent lg:w-64"
                    @click="openSearch"
                >
                    <svg
                        class="size-3.5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <span class="flex-1 text-left">Search...</span>
                    <kbd
                        class="hidden rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground sm:inline-block"
                    >
                        {{ modifierKey }}K
                    </kbd>
                </button>

                <!-- Theme toggle -->
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Toggle theme"
                    @click="toggleTheme"
                >
                    <Sun
                        class="size-4 scale-100 rotate-0 transition-transform dark:scale-0 dark:-rotate-90"
                    />
                    <Moon
                        class="absolute size-4 scale-0 rotate-90 transition-transform dark:scale-100 dark:rotate-0"
                    />
                </Button>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <div
                class="grid grid-cols-1 gap-0 md:grid-cols-[260px_1fr] lg:grid-cols-[260px_1fr_220px]"
            >
                <!-- Left Sidebar -->
                <aside
                    class="border-r border-border md:block"
                    :class="isMobileMenuOpen ? 'block' : 'hidden'"
                >
                    <div
                        class="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto py-6 pr-4"
                    >
                        <DocsSidebar
                            :chapters="chapters"
                            :current-chapter="currentChapter"
                            :current-page="currentPage"
                        />
                    </div>
                </aside>

                <!-- Main Content -->
                <main
                    class="min-w-0 px-0 py-6 md:px-8"
                    :class="{ 'hidden md:block': isMobileMenuOpen }"
                >
                    <slot />
                </main>

                <!-- Right Sidebar (Table of Contents) -->
                <aside class="hidden py-6 pl-4 lg:block">
                    <DocsTableOfContents :headings="headings ?? []" />
                </aside>
            </div>
        </div>

        <!-- Search Dialog -->
        <DocsSearchDialog ref="searchDialog" />
    </div>
</template>
