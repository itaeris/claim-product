<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { isFinished } from '../claims';
import { rupiah } from '../format';
import ClaimCard from './ClaimCard.vue';
import ProductPhoto from './ProductPhoto.vue';

const claims = ref([]);
const loading = ref(true);
const error = ref('');
const tab = ref('progres');
const activeClaims = computed(() => claims.value.filter((claim) => !isFinished(claim)));
const finishedCount = computed(() => claims.value.length - activeClaims.value.length);
const historyRows = computed(() => claims.value
    .filter(isFinished)
    .flatMap((claim) => (claim.items.length ? claim.items : [null]).map((item, index) => ({
        key: `${claim.id}-${index}`,
        claim,
        item,
    }))));

onMounted(async () => {
    try {
        claims.value = await api.get('/api/claims');
    } catch (caught) {
        error.value = caught.message;
    } finally {
        loading.value = false;
    }
});

function replace(updated) {
    claims.value = claims.value.map((claim) => (claim.id === updated.id ? updated : claim));
}
</script>

<template>
    <section class="goods-board">
        <div class="goods-tabs">
            <button type="button" class="admin-tab" :class="{ active: tab === 'progres' }" @click="tab = 'progres'">
                Progres<b v-if="activeClaims.length" class="tab-count">{{ activeClaims.length }}</b>
            </button>
            <button type="button" class="admin-tab" :class="{ active: tab === 'selesai' }" @click="tab = 'selesai'">
                Selesai<b v-if="finishedCount" class="tab-count">{{ finishedCount }}</b>
            </button>
        </div>
        <p v-if="loading" class="empty-note">Memuat progres...</p>
        <p v-else-if="error" class="form-error">{{ error }}</p>
        <div v-else-if="tab === 'selesai'" class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="historyRows.length === 0">
                            <td colspan="6">Belum ada histori.</td>
                        </tr>
                        <tr v-for="row in historyRows" :key="row.key">
                            <td>
                                <div class="product-cell">
                                    <ProductPhoto :src="row.item?.image_url || ''" :alt="row.item?.name || row.claim.number" />
                                    <span>{{ row.item?.name || row.claim.number }}</span>
                                </div>
                            </td>
                            <td>
                                <a v-if="row.claim.invoice_url" class="chip-btn" :href="row.claim.invoice_url" target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v4h4M10 12h5M10 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    {{ row.claim.invoice_number }}
                                </a>
                                <template v-else>{{ row.claim.invoice_number || row.claim.number }}</template>
                            </td>
                            <td>{{ row.claim.completed_label || row.claim.submitted_label }}</td>
                            <td>{{ row.item?.quantity || '—' }}</td>
                            <td>{{ rupiah((row.item?.unit_claim_price || 0) * (row.item?.quantity || 0)) }}</td>
                            <td><span class="status" :class="{ off: row.claim.status === 'dibatalkan' }">{{ row.claim.status_label }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p v-else-if="activeClaims.length === 0" class="empty-note">Belum ada pesanan yang sedang berjalan.</p>
        <div v-else class="claim-list">
            <ClaimCard v-for="claim in activeClaims" :key="claim.id" :claim="claim" @updated="replace" />
        </div>
    </section>
</template>
