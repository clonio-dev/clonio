<script setup lang="ts">
defineProps<{
    class?: string;
}>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 128 128"
        :class="$props.class"
    >
        <g fill-rule="evenodd">
            <linearGradient
                id="sqlite-a"
                gradientUnits="userSpaceOnUse"
                x1="63.5"
                y1="127"
                x2="63.5"
                y2="1"
            >
                <stop offset="0" stop-color="#97d9f6" />
                <stop offset="1" stop-color="#0f80cc" />
            </linearGradient>
            <path
                fill="url(#sqlite-a)"
                d="M85.3 1c-7.8 0-16.1 6.9-22.8 17.5-8.5 13.3-11.3 26-13.9 38l-.3 1.5-.1.5-.2.8-.3 1.5c-.1.3-.1.5-.2.8l-.3 1.5c-.1.4-.2.9-.4 1.3-.1.5-.2.9-.4 1.3-.1.5-.3.9-.4 1.3-.2.5-.3 1-.5 1.5-.2.5-.3.9-.5 1.4-.2.5-.3 1-.5 1.4-.2.5-.4 1-.5 1.4-.2.5-.4.9-.6 1.4s-.4.9-.6 1.4c-.2.5-.4 1-.7 1.4-.2.5-.5.9-.7 1.4-.2.5-.5 1-.7 1.4l-.8 1.4c-.3.5-.5 1-.8 1.5-.3.5-.6.9-.8 1.4l-.9 1.4c-.3.5-.6.9-.9 1.3-.3.5-.6.9-1 1.3-.3.5-.7.9-1 1.3-.3.5-.7.9-1 1.3-.4.4-.7.9-1.1 1.3-.4.4-.8.9-1.1 1.3-.4.4-.8.8-1.2 1.2l-1.2 1.2c-.4.4-.8.8-1.3 1.2l-1.3 1.2c-.4.4-.9.8-1.3 1.1-.5.4-.9.8-1.4 1.1-.5.4-.9.8-1.4 1.1l-1.5.9c-.5.3-1 .6-1.5.9-.5.3-1 .6-1.5.8-.5.3-1.1.5-1.6.8-.5.3-1.1.5-1.6.7-.6.2-1.1.5-1.7.7s-1.1.4-1.7.6c-.6.2-1.2.4-1.8.5-.6.2-1.2.3-1.8.5-.6.1-1.2.3-1.9.4-.6.1-1.3.2-1.9.3-.7.1-1.3.2-2 .2-.7.1-1.3.1-2 .1s-1.4 0-2-.1c-.3 0-.7-.1-1-.1V127h106V17L85.3 1z"
            />
            <linearGradient
                id="sqlite-b"
                gradientUnits="userSpaceOnUse"
                x1="106"
                y1="20.06"
                x2="106"
                y2="1"
            >
                <stop offset="0" stop-color="#798fa5" />
                <stop offset="1" stop-color="#acbfcf" />
            </linearGradient>
            <path
                fill="url(#sqlite-b)"
                d="M85.3 1v16c0 2.2 1.8 4 4 4H127L85.3 1z"
            />
            <path
                fill="#076db4"
                d="M33 100v4h-4v2H14v-2h-1v-4h1v-2h15v2zm3-12v4h-4v2H10v-2H9v-4h1v-2h22v2zm-3-12v4h-4v2H14v-2h-1v-4h1v-2h15v2zm3-12v4h-4v2H10v-2H9v-4h1v-2h22v2zm-3-12v4h-4v2H14v-2h-1v-4h1v-2h15v2z"
            />
        </g>
    </svg>
</template>
