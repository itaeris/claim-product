<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { rupiah } from '../format';
import ProductPhoto from './ProductPhoto.vue';

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },
    skuLimit: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['close', 'add']);
const closeButton = ref(null);

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
                    <p class="preview-note">Maks. {{ skuLimit }} pcs per SKU per tahun · invoice dari {{ product.company || product.brand }}</p>
                    <button class="claim-btn preview-add" type="button" @click="emit('add', product)">Tambahkan ke klaim</button>
                </div>
            </article>
        </div>
    </Teleport>
</template>
