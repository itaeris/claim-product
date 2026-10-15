<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';
import { claimSteps, statusLabel, stepState } from '../claims';
import { rupiah } from '../format';
import AppHeader from '../components/AppHeader.vue';

const claims = ref([]);
const loading = ref(true);
const error = ref('');

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
            <p v-if="loading" class="empty-note">Memuat progres...</p>
            <p v-else-if="error" class="form-error">{{ error }}</p>
            <p v-else-if="claims.length === 0" class="empty-note">Belum ada klaim. Pilih produk di katalog, lalu ajukan.</p>
            <div v-else class="claim-list">
                <article v-for="claim in claims" :key="claim.id" class="claim-card">
                    <div class="claim-card-head">
                        <div>
                            <strong>{{ claim.number }}</strong>
                            <small>{{ claim.submitted_label }}</small>
                        </div>
                        <span class="status" :class="{ off: claim.status === 'ditolak' }">{{ statusLabel(claim.status) }}</span>
                    </div>
                    <ol class="progress-steps">
                        <li v-for="(step, index) in claimSteps" :key="step.id" :class="stepState(claim.status, index)">
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
