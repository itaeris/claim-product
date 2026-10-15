<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { rupiah } from '../format';
import ProductPhoto from './ProductPhoto.vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    cart: {
        type: Object,
        required: true,
    },
    period: {
        type: Object,
        default: null,
    },
    submitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'change', 'remove', 'submit']);

function onKeydown(event) {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        window.addEventListener('keydown', onKeydown);
    } else {
        window.removeEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div v-if="open" class="cart-backdrop" @click="emit('close')"></div>
    <aside class="cart-drawer" :class="{ open }" :inert="!open" :aria-hidden="!open">
        <div class="claim-head">
            <h2>Klaim Saya</h2>
            <div class="claim-head-actions">
                <span class="claim-count">{{ cart.count }}</span>
                <button class="drawer-close" type="button" aria-label="Tutup keranjang" @click="emit('close')">×</button>
            </div>
        </div>
        <div class="period">
            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <div>
                <strong>{{ period?.name || 'Periode klaim' }}</strong>
                <span>{{ period?.ends_label || '' }}</span>
            </div>
        </div>
        <div class="claim-items">
            <div v-if="cart.items.length === 0" class="empty-claim">
                <div class="empty-claim-icon">🛍</div>
                <strong>Belum ada produk</strong>
                <p>Tambahkan produk dari katalog untuk mulai membuat klaim.</p>
            </div>
            <div v-for="item in cart.items" :key="item.product_id" class="claim-item">
                <ProductPhoto :src="item.image || ''" :alt="item.name" />
                <div>
                    <strong>{{ item.name }}</strong>
                    <span>{{ rupiah(item.unit_claim_price) }}</span>
                    <div class="qty">
                        <button type="button" @click="emit('change', item, -1)">−</button>
                        <em>{{ item.qty }}</em>
                        <button type="button" @click="emit('change', item, 1)">+</button>
                    </div>
                </div>
                <button class="remove" type="button" aria-label="Hapus" @click="emit('remove', item)">×</button>
            </div>
        </div>
        <div class="cart-footer">
            <div class="summary">
                <div class="summary-row"><span>Harga retail</span><strong>{{ rupiah(cart.retail_total) }}</strong></div>
                <div class="summary-row saving"><span>Hemat {{ cart.discount_percent }}%</span><strong>− {{ rupiah(cart.saving_total) }}</strong></div>
                <div class="summary-row total"><span>Total klaim</span><strong>{{ rupiah(cart.claim_total) }}</strong></div>
                <button class="claim-btn" type="button" :disabled="!cart.count || submitting" @click="emit('submit')">Ajukan Klaim</button>
            </div>
            <p class="note">Dengan mengajukan klaim, kamu menyetujui pemotongan sesuai kebijakan perusahaan.</p>
        </div>
    </aside>
</template>
