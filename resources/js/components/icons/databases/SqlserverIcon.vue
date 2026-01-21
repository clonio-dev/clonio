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
        <defs>
            <linearGradient
                id="sqlserver-a"
                x1="-1279.64"
                y1="596.473"
                x2="-1240.32"
                y2="596.473"
                gradientTransform="matrix(.97 0 0 -.97 1291.377 643.093)"
                gradientUnits="userSpaceOnUse"
            >
                <stop offset="0" stop-color="#909ca8" />
                <stop offset="1" stop-color="#c1cad3" />
            </linearGradient>
            <linearGradient
                id="sqlserver-b"
                x1="-1270.28"
                y1="581.53"
                x2="-1240.32"
                y2="581.53"
                gradientTransform="matrix(.97 0 0 -.97 1291.377 643.093)"
                gradientUnits="userSpaceOnUse"
            >
                <stop offset="0" stop-color="#717c89" />
                <stop offset="1" stop-color="#a3abb4" />
            </linearGradient>
        </defs>
        <path
            fill="#b71c1c"
            d="M90.5 37.4c-3.3 0-6.4 1-9.1 2.7-2.7-1.7-5.9-2.7-9.1-2.7-9.4 0-17.1 7.6-17.1 17.1 0 3.9 1.3 7.5 3.5 10.4L72.3 83l13.6-18.3c2.2-2.9 3.5-6.5 3.5-10.4 0-9.5-7.6-17.1-17-16.9z"
        />
        <path
            fill="url(#sqlserver-a)"
            d="M11.2 64.9 42.6 46l5.2 3v28.1l-5.2 3z"
        />
        <path fill="url(#sqlserver-b)" d="M47.8 49.1v28.1L79.2 96V67.9z" />
        <path fill="#e53935" d="M11.2 64.9v28.1l31.4 18.9V83.8z" />
        <path fill="#c62828" d="M42.6 111.9l36.6-15.9V67.9l-31.4 9.1v31.9z" />
        <path
            fill="#b71c1c"
            d="M11.2 93V64.9L0 73.7v12.9zM42.6 111.9l-31.4-18.9-6.7 4V109l38.1 19z"
        />
        <path
            fill="#f44336"
            d="M42.6 46l5.2 3L79.2 31V21L42.6 46zM4.5 49.4l6.7 4.5 31.4-19.3V21L4.5 37.5z"
        />
        <path fill="#c62828" d="m42.6 34.6 36.6-13.7L42.6 2l-38.1 16z" />
        <path fill="#e53935" d="M42.6 34.6 4.5 18.6V50l38.1-15.4z" />
        <circle fill="#fafafa" cx="72.3" cy="54.5" r="17.1" />
        <path
            fill="#b71c1c"
            d="M90.5 37.4c-3.3 0-6.4 1-9.1 2.7-2.7-1.7-5.9-2.7-9.1-2.7-9.4 0-17.1 7.6-17.1 17.1 0 3.9 1.3 7.5 3.5 10.4L72.3 83l13.6-18.3c2.2-2.9 3.5-6.5 3.5-10.4 0-9.5-7.6-17.1-17-16.9zm-4.6 22.9L72.3 78.4l-13.6-18c-1.8-2.4-2.9-5.3-2.9-8.6.2-7.5 6.3-13.5 13.9-13.4 2.6 0 5 .7 7 1.9l2.1 1.4 2.1-1.4c2-1.2 4.4-1.9 7-1.9 7.4 0 13.5 5.8 13.9 13.1v.5c0 3.2-1 6.2-2.8 8.7l-.1-.1z"
        />
        <path
            fill="#e57373"
            d="M72.3 48.5c-2.6 0-4.7 2.1-4.7 4.7s2.1 4.7 4.7 4.7 4.7-2.1 4.7-4.7c0-2.7-2.1-4.7-4.7-4.7zm0 8.2c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5z"
        />
    </svg>
</template>
