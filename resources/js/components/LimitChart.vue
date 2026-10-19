<script setup>
import { brandColor } from '../quota';
import ProductPhoto from './ProductPhoto.vue';

defineProps({
    limits: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <section class="limit-chart">
        <div class="limit-chart-head">
            <div>
                <h2>Kuota pembelian {{ limits?.year }}</h2>
                <p>
                    Setiap produk maksimal {{ limits?.total ?? 5 }} pcs per tahun. Produk lain punya kuotanya sendiri.
                    <template v-if="limits">Reset otomatis {{ limits.resets_label }} ({{ limits.days_to_reset }} hari lagi).</template>
                </p>
            </div>
            <div v-if="limits" class="limit-stats">
                <div><strong>{{ limits.products.length }}</strong><span>Produk</span></div>
                <div><strong>{{ limits.used }}</strong><span>Pcs terbeli</span></div>
                <div><strong>{{ limits.in_cart }}</strong><span>Di keranjang</span></div>
            </div>
        </div>

        <p v-if="error" class="form-error">{{ error }}</p>
        <p v-else-if="!limits" class="empty-note">Memuat kuota...</p>
        <template v-else>
            <div v-if="limits.brands.length" class="quota-legend">
                <span v-for="brand in limits.brands" :key="brand.short">
                    <i :style="{ background: brandColor(brand.short) }"></i>{{ brand.name }} · {{ brand.qty }} pcs
                </span>
            </div>

            <p v-if="limits.products.length === 0" class="limit-empty">
                Belum ada pembelian tahun ini. Setiap produk bisa dibeli sampai {{ limits.total }} pcs.
            </p>
            <ul v-else class="limit-bars">
                <li v-for="row in limits.products" :key="row.product_id">
                    <div class="product-cell">
                        <ProductPhoto :src="row.image_url || ''" :alt="row.name" />
                        <div>
                            <strong>{{ row.name }}</strong>
                            <small class="claim-mini">{{ row.brand }}</small>
                            <div class="limit-meter" :class="{ full: row.remaining === 0 }" aria-hidden="true">
                                <span v-for="cell in limits.total" :key="cell" :class="{ on: cell <= row.qty }"></span>
                            </div>
                        </div>
                    </div>
                    <div class="limit-count">
                        <strong>{{ row.qty }}/{{ limits.total }}</strong>
                    </div>
                </li>
            </ul>
        </template>
    </section>
</template>
