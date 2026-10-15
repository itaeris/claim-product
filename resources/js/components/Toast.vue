<script setup>
import { useToast } from '../toast';

const { message, visible } = useToast();
</script>

<template>
    <div class="toast" :class="{ show: visible }" role="status">{{ message }}</div>
</template>
