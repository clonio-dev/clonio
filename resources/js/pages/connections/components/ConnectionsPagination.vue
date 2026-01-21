<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';
import { computed } from 'vue';

interface PaginationLink {
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
}

const props = defineProps<Props>();

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
        class="flex items-center justify-center gap-1 pt-4"
        aria-label="Pagination"
    >
        <!-- Previous button -->
        <Button
            v-if="prevUrl"
            variant="ghost"
            size="icon"
            as-child
            class="h-9 w-9 transition-transform hover:-translate-x-0.5"
        >
            <Link :href="prevUrl" preserve-scroll>
                <ChevronLeft class="h-4 w-4" />
                <span class="sr-only">Previous page</span>
            </Link>
        </Button>
        <Button
            v-else
            variant="ghost"
            size="icon"
            disabled
            class="h-9 w-9 opacity-50"
        >
            <ChevronLeft class="h-4 w-4" />
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
                    class="flex h-9 w-9 items-center justify-center text-muted-foreground"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </div>

                <!-- Page number -->
                <Button
                    v-else
                    :variant="item.active ? 'default' : 'ghost'"
                    size="icon"
                    as-child
                    class="h-9 w-9 transition-all"
                    :class="{
                        'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-500':
                            item.active,
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
            v-if="nextUrl"
            variant="ghost"
            size="icon"
            as-child
            class="h-9 w-9 transition-transform hover:translate-x-0.5"
        >
            <Link :href="nextUrl" preserve-scroll>
                <ChevronRight class="h-4 w-4" />
                <span class="sr-only">Next page</span>
            </Link>
        </Button>
        <Button
            v-else
            variant="ghost"
            size="icon"
            disabled
            class="h-9 w-9 opacity-50"
        >
            <ChevronRight class="h-4 w-4" />
            <span class="sr-only">Next page</span>
        </Button>
    </nav>
</template>
