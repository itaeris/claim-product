<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { rupiah } from '../format';
import ProductPhoto from './ProductPhoto.vue';

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    quotaLeft: {
        type: Number,
        default: null,
    },
    limit: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'add']);
const closeButton = ref(null);

const quotaFull = computed(() => props.quotaLeft === 0);

function quotaCell(index) {
    const used = props.limit?.used ?? 0;

    if (index <= used) {
        return 'on';
    }

    return index <= (props.limit?.total ?? 0) - (props.quotaLeft ?? 0) ? 'cart' : '';
}

const saving = computed(() => Math.max(0, (props.product?.price ?? 0) - (props.product?.claim_price ?? 0)));

function onKeydown(event) {
    if (event.key === 'Escape') {
        emit('close');
    }
}

watch(() => props.product, async (product) => {
    if (!product) {
        return;
    }

    await nextTick();
    closeButton.value?.focus();
});

watch(() => props.product, (product) => {
    if (product) {
        window.addEventListener('keydown', onKeydown);
    } else {
        window.removeEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="product" class="product-preview" @click.self="emit('close')">
            <article class="preview-card" role="dialog" aria-modal="true" :aria-label="product.name">
                <div class="preview-photo">
                    <ProductPhoto :src="product.image || ''" :alt="product.name" />
                    <span class="discount">{{ product.discount }}% OFF</span>
                </div>
                <div class="preview-body">
                    <button ref="closeButton" class="preview-close" type="button" aria-label="Tutup" @click="emit('close')">×</button>
                    <span class="product-cat">{{ product.category }}</span>
                    <h2>{{ product.name }}</h2>
                    <p class="preview-brand">{{ product.brand }}</p>
                    <dl class="preview-details">
                        <div>
                            <dt>Harga retail</dt>
                            <dd>{{ rupiah(product.price) }}</dd>
                        </div>
                        <div>
                            <dt>Diskon karyawan</dt>
                            <dd>{{ product.discount }}%</dd>
                        </div>
                        <div>
                            <dt>Hemat</dt>
                            <dd>{{ rupiah(saving) }}</dd>
                        </div>
                        <div class="preview-claim">
                            <dt>Harga klaim</dt>
                            <dd>{{ rupiah(product.claim_price) }}</dd>
                        </div>
                    </dl>
                    <div v-if="limit" class="preview-quota">
                        <div class="limit-meter" :class="{ full: quotaFull }" aria-hidden="true">
                            <span v-for="cell in limit.total" :key="cell" :class="quotaCell(cell)"></span>
                        </div>
                        <span>Sisa kuota kamu <strong>{{ quotaLeft }}/{{ limit.total }} pcs</strong> untuk semua brand · reset {{ limit.resets_label }}</span>
                    </div>
                    <p class="preview-note">Invoice dari {{ product.company || product.brand }}</p>
                    <button class="claim-btn preview-add" type="button" :disabled="quotaFull" @click="emit('add', product)">
                        {{ quotaFull ? 'Kuota tahun ini habis' : 'Tambahkan ke keranjang' }}
                    </button>
                </div>
            </article>
        </div>
    </Teleport>
</template>
