<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { brandLogo } from '../brandLogo';
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
    warehouseAddress: {
        type: String,
        default: '',
    },
    submitting: {
        type: Boolean,
        default: false,
    },
});

const fulfillment = defineModel('fulfillment', { type: String, default: 'pickup' });
const address = defineModel('address', { type: String, default: '' });
const emit = defineEmits(['close', 'change', 'remove', 'submit']);

const groups = computed(() => (props.cart.groups ?? []).map((group) => ({
    ...group,
    items: props.cart.items.filter((item) => item.brand_id === group.brand_id),
})));
const shippingTotal = computed(() => (fulfillment.value === 'delivery' ? props.cart.delivery_fee_total ?? 0 : 0));
const grandTotal = computed(() => props.cart.claim_total + shippingTotal.value);

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
            <h2>Keranjang</h2>
            <div class="claim-head-actions">
                <span class="claim-count">{{ cart.count }}</span>
                <button class="drawer-close" type="button" aria-label="Tutup keranjang" @click="emit('close')">×</button>
            </div>
        </div>
        <div class="period">
            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            <div>
                <strong>{{ period?.name || 'Employee purchase' }}</strong>
                <span>Maks. {{ cart.sku_limit ?? 5 }} pcs per SKU per tahun</span>
            </div>
        </div>
        <div class="claim-items">
            <div v-if="cart.items.length === 0" class="empty-claim">
                <div class="empty-claim-icon">🛍</div>
                <strong>Belum ada produk</strong>
                <p>Tambahkan produk dari katalog. Pesanan otomatis dipisah per brand.</p>
            </div>
            <section v-for="group in groups" :key="group.brand_id" class="cart-group">
                <div class="cart-group-head">
                    <span class="cart-group-logo">
                        <img v-if="brandLogo({ short: group.brand_short })" :src="brandLogo({ short: group.brand_short })" :alt="group.brand_name">
                        <template v-else>{{ group.brand_short }}</template>
                    </span>
                    <div>
                        <strong>{{ group.brand_name }}</strong>
                        <small>{{ group.company_name }} · diskon {{ group.discount_percent }}%</small>
                    </div>
                </div>
                <div v-for="item in group.items" :key="item.product_id" class="claim-item">
                    <ProductPhoto :src="item.image || ''" :alt="item.name" />
                    <div>
                        <strong>{{ item.name }}</strong>
                        <span>{{ rupiah(item.unit_claim_price) }}</span>
                        <div class="qty">
                            <button type="button" @click="emit('change', item, -1)">−</button>
                            <em>{{ item.qty }}</em>
                            <button type="button" :disabled="item.qty >= item.limit_remaining" @click="emit('change', item, 1)">+</button>
                            <small class="qty-limit">kuota {{ item.limit_remaining }}</small>
                        </div>
                    </div>
                    <button class="remove" type="button" aria-label="Hapus" @click="emit('remove', item)">×</button>
                </div>
                <div class="cart-group-total">
                    <span>Invoice {{ group.brand_short }}</span>
                    <strong>{{ rupiah(group.claim_total + (fulfillment === 'delivery' ? group.shipping_fee : 0)) }}</strong>
                </div>
            </section>
        </div>
        <div class="cart-footer">
            <div class="fulfillment" role="radiogroup" aria-label="Metode pengambilan">
                <button type="button" role="radio" :aria-checked="fulfillment === 'pickup'" :class="{ active: fulfillment === 'pickup' }" @click="fulfillment = 'pickup'">
                    <strong>Ambil di gudang</strong>
                    <small>Gratis</small>
                </button>
                <button type="button" role="radio" :aria-checked="fulfillment === 'delivery'" :class="{ active: fulfillment === 'delivery' }" @click="fulfillment = 'delivery'">
                    <strong>Kirim</strong>
                    <small>Ongkir per invoice</small>
                </button>
            </div>
            <label v-if="fulfillment === 'delivery'" class="field cart-address">
                <span>Alamat pengiriman</span>
                <textarea v-model="address" rows="2" maxlength="500" placeholder="Kosongkan untuk dikirim ke kantor"></textarea>
            </label>
            <p v-else-if="warehouseAddress" class="cart-pickup">Ambil di {{ warehouseAddress }}</p>
            <div class="summary">
                <div class="summary-row"><span>Harga RSP</span><strong>{{ rupiah(cart.retail_total) }}</strong></div>
                <div class="summary-row saving"><span>Diskon karyawan</span><strong>− {{ rupiah(cart.saving_total) }}</strong></div>
                <div class="summary-row"><span>Ongkir</span><strong>{{ fulfillment === 'delivery' ? rupiah(shippingTotal) : 'Gratis' }}</strong></div>
                <div class="summary-row total"><span>Total bayar</span><strong>{{ rupiah(grandTotal) }}</strong></div>
                <button class="claim-btn" type="button" :disabled="!cart.count || submitting" @click="emit('submit')">
                    {{ submitting ? 'Mengajukan...' : 'Ajukan Pesanan' }}
                </button>
            </div>
            <p class="note">
                <template v-if="groups.length > 1">Dibuat {{ groups.length }} invoice terpisah sesuai brand. </template>
                Invoice dikirim setelah stok dicek, lalu transfer ke PT brand dan unggah bukti di menu Progres.
            </p>
        </div>
    </aside>
</template>
