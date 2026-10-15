<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { goodsSteps, statusLabel, stepState } from '../claims';
import { rupiah } from '../format';
import AppHeader from '../components/AppHeader.vue';
import ProductPhoto from '../components/ProductPhoto.vue';

const claims = ref([]);
const loading = ref(true);
const error = ref('');
const goodsTab = ref('progres');
const activeClaims = computed(() => claims.value.filter((claim) => claim.status !== 'selesai'));
const historyRows = computed(() => claims.value
    .filter((claim) => claim.status === 'selesai')
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
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <main class="admin-main">
            <div class="admin-heading">
                <div>
                    <h1>Progres klaim</h1>
                    <p>Kamu bisa melihat status setiap klaim produk di sini.</p>
                </div>
            </div>
            <div class="goods-tabs">
                <button type="button" class="admin-tab" :class="{ active: goodsTab === 'progres' }" @click="goodsTab = 'progres'">Progres</button>
                <button type="button" class="admin-tab" :class="{ active: goodsTab === 'selesai' }" @click="goodsTab = 'selesai'">Selesai</button>
            </div>
            <p v-if="loading" class="empty-note">Memuat progres...</p>
            <p v-else-if="error" class="form-error">{{ error }}</p>
            <div v-else-if="goodsTab === 'selesai'" class="table-card">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Nomor</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="historyRows.length === 0">
                                <td colspan="5">Belum ada histori.</td>
                            </tr>
                            <tr v-for="row in historyRows" :key="row.key">
                                <td>
                                    <div class="product-cell">
                                        <ProductPhoto :src="row.item?.image_url || ''" :alt="row.item?.name || row.claim.number" />
                                        <span>{{ row.item?.name || row.claim.number }}</span>
                                    </div>
                                </td>
                                <td>{{ row.claim.number }}</td>
                                <td>{{ row.claim.submitted_label }}</td>
                                <td>{{ row.item?.quantity || '—' }}</td>
                                <td>{{ rupiah((row.item?.unit_claim_price || 0) * (row.item?.quantity || 0)) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <p v-else-if="activeClaims.length === 0" class="empty-note">Belum ada klaim yang sedang berjalan.</p>
            <div v-else class="claim-list">
                <article v-for="claim in activeClaims" :key="claim.id" class="claim-card">
                    <div class="claim-card-head">
                        <div>
                            <strong>{{ claim.number }}</strong>
                            <small>{{ claim.submitted_label }}</small>
                        </div>
                        <span class="status" :class="{ off: claim.status === 'ditolak' }">{{ statusLabel(claim.status) }}</span>
                    </div>
                    <ol class="progress-steps">
                        <li v-for="(step, index) in goodsSteps" :key="step.id" :class="stepState(claim.status, index, goodsSteps)">
                            <span>{{ step.label }}</span>
                        </li>
                    </ol>
                    <ul class="claim-lines">
                        <li v-for="item in claim.items" :key="item.name">
                            <span>{{ item.name }} × {{ item.quantity }}</span>
                            <strong>{{ rupiah(item.unit_claim_price * item.quantity) }}</strong>
                        </li>
                    </ul>
                    <div class="claim-card-total">
                        <span>Total klaim</span>
                        <strong>{{ rupiah(claim.claim_total) }}</strong>
                    </div>
                </article>
            </div>
        </main>
    </div>
</template>
