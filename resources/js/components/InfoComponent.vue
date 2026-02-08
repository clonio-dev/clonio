<script setup lang="ts">
import { LucideIcon } from 'lucide-vue-next';

const props = defineProps<{
    icon: LucideIcon;
    title: string;
    description: string;
}>();
</script>

<template>
    <div
        class="rounded-xl border border-dashed border-slate-300 bg-slate-100 p-6 dark:border-slate-700 dark:bg-[#1a2733]"
    >
        <div class="flex gap-4">
            <component
                :is="props.icon"
                class="size-8 shrink-0 stroke-blue-600"
            />
            <div class="flex flex-col gap-1">
                <h4
                    class="text-lg font-bold text-slate-900 dark:text-white"
                    v-html="props.title"
                ></h4>
                <p
                    class="text-sm leading-relaxed text-slate-500 dark:text-[#92adc9]"
                    v-html="props.description"
                ></p>
            </div>
        </div>
    </div>
</template>
