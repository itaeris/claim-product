<script setup>
import { computed } from 'vue';

const props = defineProps({
    page: { type: Number, required: true },
    perPage: { type: Number, required: true },
    total: { type: Number, required: true },
    label: { type: String, default: 'data' },
});

const emit = defineEmits(['update:page']);

const pages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1));
const to = computed(() => Math.min(props.total, props.page * props.perPage));

const items = computed(() => {
    const last = pages.value;
    const current = props.page;

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const around = [current - 1, current, current + 1].filter((value) => value > 1 && value < last);
    const list = [1];

    if (around[0] > 2) {
        list.push('…');
    }

    list.push(...around);

    if (around[around.length - 1] < last - 1) {
        list.push('…');
    }

    list.push(last);

    return list;
});

function go(target) {
    if (target >= 1 && target <= pages.value && target !== props.page) {
        emit('update:page', target);
    }
}
</script>

<template>
    <nav class="pager" aria-label="Pagination">
        <span class="pager-info">{{ from }}–{{ to }} dari {{ total }} {{ label }}</span>
        <div v-if="pages > 1" class="pager-pages">
            <button type="button" aria-label="Halaman sebelumnya" :disabled="page === 1" @click="go(page - 1)">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m14 7-5 5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <template v-for="(item, index) in items" :key="`${item}-${index}`">
                <span v-if="item === '…'" class="pager-gap">…</span>
                <button
                    v-else
                    type="button"
                    :class="{ active: item === page }"
                    :aria-current="item === page ? 'page' : undefined"
                    @click="go(item)"
                >{{ item }}</button>
            </template>
            <button type="button" aria-label="Halaman berikutnya" :disabled="page === pages" @click="go(page + 1)">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m10 7 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </nav>
</template>
