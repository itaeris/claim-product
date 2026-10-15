<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Pilih',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const open = ref(false);
const root = ref(null);
const menu = ref(null);
const menuStyle = ref({});

const selected = computed(() => props.options.find((option) => String(option.value) === String(props.modelValue)));

function place() {
    const rect = root.value?.getBoundingClientRect();

    if (!rect) {
        return;
    }

    const spaceBelow = window.innerHeight - rect.bottom;
    const openUp = spaceBelow < 220 && rect.top > spaceBelow;

    menuStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        top: openUp ? 'auto' : `${rect.bottom + 6}px`,
        bottom: openUp ? `${window.innerHeight - rect.top + 6}px` : 'auto',
    };
}

function onDocumentClick(event) {
    if (root.value?.contains(event.target) || menu.value?.contains(event.target)) {
        return;
    }

    open.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

function addListeners() {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('scroll', place, true);
    window.addEventListener('resize', place);
}

function removeListeners() {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
}

watch(open, async (isOpen) => {
    if (!isOpen) {
        removeListeners();

        return;
    }

    await nextTick();
    place();
    addListeners();
});

onBeforeUnmount(removeListeners);

function toggle() {
    open.value = !open.value;
}

function choose(value) {
    emit('update:modelValue', value);
    open.value = false;
}
</script>

<template>
    <div ref="root" class="app-select" :class="{ compact, open }">
        <button class="app-select-btn" type="button" :aria-expanded="open" aria-haspopup="listbox" @click="toggle">
            <span>{{ selected?.label || placeholder }}</span>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <Teleport to="body">
            <div v-if="open" ref="menu" class="app-select-menu" role="listbox" :style="menuStyle">
                <button
                    v-for="option in options"
                    :key="option.value"
                    type="button"
                    role="option"
                    :aria-selected="String(option.value) === String(modelValue)"
                    :class="{ active: String(option.value) === String(modelValue) }"
                    @click="choose(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>
        </Teleport>
    </div>
</template>
