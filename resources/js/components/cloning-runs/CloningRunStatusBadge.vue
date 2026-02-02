<script setup lang="ts">
import { statusConfig } from '@/components/cloning-runs/types';
import { Badge } from '@/components/ui/badge';
import type { CloningRunStatus } from '@/types/cloning.types';
import { computed } from 'vue';

interface Props {
    status: CloningRunStatus;
}

const props = defineProps<Props>();

const currentStatus = computed(
    () => statusConfig[props.status] || statusConfig.cancelled,
);
</script>

<template>
    <Badge
        variant="outline"
        class="gap-1.5 text-xs"
        :class="currentStatus.badgeClass"
    >
        <component
            :is="currentStatus.icon"
            class="size-3"
            :class="currentStatus.iconClass"
        />
        {{ currentStatus.label }}
    </Badge>
</template>
