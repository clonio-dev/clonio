<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';
import { computed } from 'vue';

export interface PaginationLink {
    url: string | null;
    label: string;
    page: number | null;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    currentPage: number;
    lastPage: number;
    prevUrl: string | null;
    nextUrl: string | null;
    from: number;
    to: number;
    total: number;
    name?: string;
    pluralName?: string;
    variant?: 'default' | 'simple';
    display?: 'everytime' | 'when-necessary';
}

const props = withDefaults(defineProps<Props>(), {
    name: 'item',
    pluralName: 'items',
    variant: 'default',
    display: 'when-necessary',
});

const shouldDisplay = computed(() => props.display === 'everytime' || props.lastPage > 1);

// Filter out prev/next labels and get page number links
const pageLinks = computed(() => {
    return props.links.filter(
        (link) =>
            link.page !== null &&
            !link.label.includes('Previous') &&
            !link.label.includes('Next'),
    );
});

// Create a smart pagination display (show first, last, and around current)
const visiblePages = computed(() => {
    const pages = pageLinks.value;
    const current = props.currentPage;
    const total = props.lastPage;

    if (total <= 7) {
        return pages;
    }

    const result: (PaginationLink | { type: 'ellipsis'; key: string })[] = [];

    // Always show first page
    if (pages[0]) {
        result.push(pages[0]);
    }

    // Add ellipsis if needed
    if (current > 3) {
        result.push({ type: 'ellipsis', key: 'start-ellipsis' });
    }

    // Pages around current
    for (
        let i = Math.max(2, current - 1);
        i <= Math.min(total - 1, current + 1);
        i++
    ) {
        const page = pages.find((p) => p.page === i);
        if (page) {
            result.push(page);
        }
    }

    // Add ellipsis if needed
    if (current < total - 2) {
        result.push({ type: 'ellipsis', key: 'end-ellipsis' });
    }

    // Always show last page
    const lastPageLink = pages.find((p) => p.page === total);
    if (lastPageLink && total > 1) {
        result.push(lastPageLink);
    }

    return result;
});

function isEllipsis(
    item: PaginationLink | { type: 'ellipsis'; key: string },
): item is { type: 'ellipsis'; key: string } {
    return 'type' in item && item.type === 'ellipsis';
}
</script>

<template>
    <nav
        v-if="shouldDisplay"
        class="flex items-center justify-between"
        aria-label="Pagination"
    >
        <div class="shrink-0">
            <p class="text-slate-700 dark:text-slate-100">
            Showing {{ props.from }} - {{ props.to }} of {{ props.total }}
            {{ props.total === 1 ? props.name : props.pluralName }}
            </p>
        </div>
        <div class="flex items-center justify-center gap-1">
            <template v-if="props.variant === 'default'">
                <!-- Previous button -->
                <Button
                    v-if="props.prevUrl"
                    variant="ghost"
                    size="icon"
                    as-child
                    class="size-9 transition-transform hover:-translate-x-0.5"
                >
                    <Link :href="props.prevUrl" preserve-scroll>
                        <ChevronLeft class="size-4" />
                        <span class="sr-only">Previous page</span>
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="ghost"
                    size="icon"
                    disabled
                    class="size-9 opacity-50"
                >
                    <ChevronLeft class="size-4" />
                    <span class="sr-only">Previous page</span>
                </Button>

                <!-- Page numbers -->
                <div class="flex items-center gap-1">
                    <template
                        v-for="item in visiblePages"
                        :key="isEllipsis(item) ? item.key : item.page"
                    >
                        <!-- Ellipsis -->
                        <div
                            v-if="isEllipsis(item)"
                            class="flex size-9 items-center justify-center text-muted-foreground"
                        >
                            <MoreHorizontal class="size-4" />
                        </div>

                        <!-- Page number -->
                        <Button
                            v-else
                            :variant="item.active ? 'default' : 'ghost'"
                            size="icon"
                            as-child
                            class="size-9 transition-all"
                            :class="{
                                'text-white shadow-md': item.active,
                                'hover:bg-muted': !item.active,
                            }"
                        >
                            <Link
                                v-if="item.url && !item.active"
                                :href="item.url"
                                preserve-scroll
                            >
                                {{ item.page }}
                            </Link>
                            <span v-else>{{ item.page }}</span>
                        </Button>
                    </template>
                </div>

                <!-- Next button -->
                <Button
                    v-if="props.nextUrl"
                    variant="ghost"
                    size="icon"
                    as-child
                    class="size-9 transition-transform hover:translate-x-0.5"
                >
                    <Link :href="props.nextUrl" preserve-scroll>
                        <ChevronRight class="size-4" />
                        <span class="sr-only">Next page</span>
                    </Link>
                </Button>
                <Button
                    v-else
                    variant="ghost"
                    size="icon"
                    disabled
                    class="size-9 opacity-50"
                >
                    <ChevronRight class="size-4" />
                    <span class="sr-only">Next page</span>
                </Button>
            </template>
            <template v-if="variant === 'simple'">
                <!-- Previous button -->
                <Button :disabled="!props.prevUrl" variant="ghost">
                    <Link
                        v-if="props.prevUrl"
                        :href="props.prevUrl"
                        preserve-scroll
                    >
                        <span>Previous</span>
                    </Link>
                    <span v-else>Previous</span>
                </Button>

                <!-- Next button -->
                <Button :disabled="!props.nextUrl" variant="ghost">
                    <Link
                        v-if="props.nextUrl"
                        :href="props.nextUrl"
                        preserve-scroll
                    >
                        <span>Next</span>
                    </Link>
                    <span v-else>Next</span>
                </Button>
            </template>
        </div>
    </nav>
</template>
