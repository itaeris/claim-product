<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { brandLogo } from '../brandLogo';
import { rupiah } from '../format';
import LimitChart from '../components/LimitChart.vue';
import ProductPhoto from '../components/ProductPhoto.vue';
import ProfileShell from '../components/ProfileShell.vue';
import { quota } from '../quota';

const claims = ref([]);
const loading = ref(true);
const error = ref('');
const limitError = ref('');

const rows = computed(() => claims.value.flatMap((claim) => claim.items.map((item, index) => ({
    key: `${claim.id}-${index}`,
    claim,
    item,
}))));

onMounted(async () => {
    api.get('/api/limits')
        .then((data) => {
            quota.value = data;
        })
        .catch((caught) => {
            limitError.value = caught.message;
        });

    try {
        claims.value = await api.get('/api/claims');
    } catch (caught) {
        error.value = caught.message;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <ProfileShell description="Kuota pembelian tahun ini dan semua produk yang pernah kamu pesan.">
        <LimitChart :limits="quota" :error="limitError" />

        <section class="history-section">
            <h2>Semua pembelian</h2>
            <p v-if="loading" class="empty-note">Memuat riwayat...</p>
            <p v-else-if="error" class="form-error">{{ error }}</p>
            <div v-else class="table-card">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Invoice</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Harga klaim</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="6">Belum ada produk yang dibeli.</td>
                            </tr>
                            <tr v-for="row in rows" :key="row.key">
                                <td>
                                    <div class="product-cell">
                                        <ProductPhoto :src="row.item.image_url || ''" :alt="row.item.name" />
                                        <div>
                                            <strong>{{ row.item.name }}</strong>
                                            <small class="claim-mini history-brand">
                                                <img v-if="brandLogo({ short: row.claim.brand.short })" :src="brandLogo({ short: row.claim.brand.short })" alt="">
                                                {{ row.claim.brand.name }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a v-if="row.claim.invoice_url" class="chip-btn" :href="row.claim.invoice_url" target="_blank" rel="noopener">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v4h4M10 12h5M10 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        {{ row.claim.invoice_number }}
                                    </a>
                                    <template v-else>{{ row.claim.invoice_number || row.claim.number }}</template>
                                </td>
                                <td>{{ row.claim.submitted_label }}</td>
                                <td>{{ row.item.quantity }} pcs</td>
                                <td>{{ rupiah(row.item.unit_claim_price * row.item.quantity) }}</td>
                                <td><span class="status" :class="{ off: row.claim.status === 'dibatalkan' }">{{ row.claim.status_label }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </ProfileShell>
</template>
