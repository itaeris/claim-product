<script setup>
import { computed } from 'vue';
import { brandColor } from '../quota';
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

const cells = computed(() => {
    if (!props.limits) {
        return [];
    }

    const filled = props.limits.products.flatMap((row) => Array.from({ length: row.qty }, () => ({
        color: brandColor(row.brand_short),
        label: `${row.name} (${row.brand_short})`,
    })));

    return Array.from({ length: props.limits.total }, (_, index) => filled[index] ?? null);
});
</script>

<template>
    <section class="limit-chart">
        <div class="limit-chart-head">
            <div>
                <h2>Kuota pembelian {{ limits?.year }}</h2>
                <p>
                    Maks. {{ limits?.total ?? 5 }} pcs per tahun, bebas dipakai untuk produk dan brand apa pun.
                    <template v-if="limits">Reset otomatis {{ limits.resets_label }} ({{ limits.days_to_reset }} hari lagi).</template>
                </p>
            </div>
            <div v-if="limits" class="limit-stats">
                <div><strong>{{ limits.used }}</strong><span>Terpakai</span></div>
                <div :class="{ warn: limits.remaining === 0 }"><strong>{{ limits.remaining }}</strong><span>Sisa</span></div>
                <div><strong>{{ limits.in_cart }}</strong><span>Di keranjang</span></div>
            </div>
        </div>

        <p v-if="error" class="form-error">{{ error }}</p>
        <p v-else-if="!limits" class="empty-note">Memuat kuota...</p>
        <template v-else>
            <div class="quota-track" role="meter" :aria-valuenow="limits.used" aria-valuemin="0" :aria-valuemax="limits.total" :aria-label="`Kuota terpakai ${limits.used} dari ${limits.total} pcs`">
                <span
                    v-for="(cell, index) in cells"
                    :key="index"
                    :class="{ on: cell }"
                    :style="cell ? { background: cell.color } : null"
                    :title="cell?.label ?? 'Belum dipakai'"
                ></span>
            </div>
            <div v-if="limits.brands.length" class="quota-legend">
                <span v-for="brand in limits.brands" :key="brand.short">
                    <i :style="{ background: brandColor(brand.short) }"></i>{{ brand.name }} · {{ brand.qty }} pcs
                </span>
            </div>

            <p v-if="limits.products.length === 0" class="limit-empty">
                Belum ada pembelian tahun ini. Kamu masih bisa membeli {{ limits.total }} pcs dari brand mana saja.
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
                    <div class="limit-count">
                        <strong>{{ row.qty }} pcs</strong>
                    </div>
                </li>
            </ul>
        </template>
    </section>
</template>
