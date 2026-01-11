<script setup lang="ts">
interface Props {
    step: number|string;
    active?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    active: false,
});
</script>

<template>
    <div
        class="flex size-12 items-center justify-center rounded-full border-6 text-lg"
        :class="{
            'border-indigo-400 font-bold text-indigo-600': props.active,
            'font-normal text-gray-600': !props.active,
        }"
    >
        {{ props.step }}
    </div>
</template>
