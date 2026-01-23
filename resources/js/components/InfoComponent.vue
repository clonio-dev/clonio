<script setup lang="ts">
import { LucideIcon } from 'lucide-vue-next';

const props = defineProps<{
    icon: LucideIcon,
    title: string,
    description: string,
}>();
</script>

<template>
    <div class="p-6 rounded-xl bg-slate-100 dark:bg-[#1a2733] border border-dashed border-slate-300 dark:border-slate-700">
        <div class="flex gap-4">
            <component :is="props.icon" class="shrink-0 size-8 stroke-blue-600" />
            <div class="flex flex-col gap-1">
                <h4 class="text-slate-900 dark:text-white font-bold text-lg" v-html="props.title"></h4>
                <p class="text-slate-500 dark:text-[#92adc9] text-sm leading-relaxed" v-html="props.description"></p>
            </div>
        </div>
    </div>
</template>
