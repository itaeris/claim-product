<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';
import { claimStatuses, statusLabel } from '../claims';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import AppSelect from '../components/AppSelect.vue';

const { show } = useToast();
const claims = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        claims.value = await api.get('/api/claims/all');
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

async function updateStatus(claim, status) {
    const previous = claim.status;
    claim.status = status;

    try {
        const updated = await api.patch(`/api/claims/${claim.id}`, { status });
        claims.value = claims.value.map((item) => (item.id === claim.id ? updated : item));
        show(`Status ${claim.number} menjadi ${statusLabel(status)}`);
    } catch (error) {
        claim.status = previous;
        show(error.message);
    }
}
</script>

<template>
    <AdminShell>
        <section class="table-card admin-layout">
            <div v-if="loading" class="empty">Memuat klaim...</div>
            <div v-else-if="claims.length === 0" class="empty">Belum ada klaim.</div>
            <div v-else class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Karyawan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="claim in claims" :key="claim.id">
                            <td>
                                <strong>{{ claim.number }}</strong>
                                <small class="claim-mini">{{ claim.items.map((item) => `${item.name} × ${item.quantity}`).join(', ') }}</small>
                            </td>
                            <td>{{ claim.user?.name }}<br><small>{{ claim.user?.team }}</small></td>
                            <td>{{ claim.submitted_label }}</td>
                            <td>{{ rupiah(claim.claim_total) }}</td>
                            <td>
                                <AppSelect
                                    compact
                                    :model-value="claim.status"
                                    :options="claimStatuses.map((status) => ({ value: status.id, label: status.label }))"
                                    @update:model-value="updateStatus(claim, $event)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminShell>
</template>
