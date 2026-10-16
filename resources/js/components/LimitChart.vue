<script setup>
import { computed } from 'vue';
import ProductPhoto from './ProductPhoto.vue';

const props = defineProps({
    limits: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: '',
    },
});

const perSku = computed(() => props.limits?.per_sku ?? 5);
const cells = computed(() => Array.from({ length: perSku.value }, (_, index) => index));
</script>

<template>
    <section class="limit-chart">
        <div class="limit-chart-head">
            <div>
                <h2>Kuota pembelian {{ limits?.year }}</h2>
                <p>
                    Maks. {{ perSku }} pcs per SKU, berlaku untuk semua brand.
                    <template v-if="limits">Reset otomatis {{ limits.resets_label }} ({{ limits.days_to_reset }} hari lagi).</template>
                </p>
            </div>
            <div v-if="limits" class="limit-stats">
                <div><strong>{{ limits.totals.skus }}</strong><span>SKU dibeli</span></div>
                <div><strong>{{ limits.totals.pcs }}</strong><span>Total pcs</span></div>
                <div :class="{ warn: limits.totals.full }"><strong>{{ limits.totals.full }}</strong><span>SKU penuh</span></div>
            </div>
        </div>

        <p v-if="error" class="form-error">{{ error }}</p>
        <p v-else-if="!limits" class="empty-note">Memuat kuota...</p>
        <p v-else-if="limits.products.length === 0" class="limit-empty">
            Belum ada pembelian tahun ini. Setiap SKU masih bisa dibeli {{ perSku }} pcs.
        </p>
        <ul v-else class="limit-bars">
            <li v-for="row in limits.products" :key="row.product_id">
                <div class="product-cell">
                    <ProductPhoto :src="row.image_url || ''" :alt="row.name" />
                    <div>
                        <strong>{{ row.name }}</strong>
                        <small class="claim-mini">{{ row.brand }}</small>
                    </div>
                </div>
                <div class="limit-meter" :class="{ full: row.remaining === 0 }" role="meter" :aria-valuenow="row.used" aria-valuemin="0" :aria-valuemax="row.allowed" :aria-label="`${row.name}: ${row.used} dari ${row.allowed} pcs`">
                    <span v-for="cell in cells" :key="cell" :class="{ on: cell < row.used }"></span>
                </div>
                <div class="limit-count">
                    <strong>{{ row.used }}/{{ row.allowed }}</strong>
                    <small>{{ row.remaining ? `sisa ${row.remaining}` : 'penuh' }}</small>
                </div>
            </li>
        </ul>
    </section>
</template>
