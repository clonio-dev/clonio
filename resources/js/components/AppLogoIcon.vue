<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="215 213 83 133"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient id="ghostGradient" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#6EE7B7" /> <stop offset="100%" stop-color="#3B82F6" /> </linearGradient>
        </defs>

        <g transform="translate(146, 146) scale(0.43)">
            <path d="M 160, 250
                 A 96, 96 0 1, 1 352, 250  L 352, 420                Q 352, 490 256, 440       Q 160, 390 160, 300       Z"
                  fill="url(#ghostGradient)"/>

            <ellipse cx="215" cy="260" rx="18" ry="28" fill="currentColor"/>
            <ellipse cx="297" cy="260" rx="18" ry="28" fill="currentColor"/>
        </g>
    </svg>
</template>
