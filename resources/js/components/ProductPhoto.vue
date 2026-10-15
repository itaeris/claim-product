<script setup>
defineProps({
    src: {
        type: String,
        default: '',
    },
    alt: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <img v-if="src" :src="src" :alt="alt">
    <span v-else class="photo-placeholder" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
            <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="9" cy="10" r="1.5" fill="currentColor"/>
            <path d="m4.5 16 4.2-4.2 2.8 2.8L15.5 10l4 4.2" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
        </svg>
    </span>
</template>
