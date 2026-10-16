<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../api';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import ProductPhoto from '../components/ProductPhoto.vue';

const { show } = useToast();
const requests = ref([]);
const loading = ref(true);
const tab = ref('menunggu');
const busyId = ref(null);
const notes = reactive({});
const visible = computed(() => requests.value.filter((item) => (tab.value === 'menunggu' ? item.status === 'menunggu' : item.status !== 'menunggu')));
const pendingCount = computed(() => requests.value.filter((item) => item.status === 'menunggu').length);

onMounted(async () => {
    try {
        requests.value = await api.get('/api/limit-requests');
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

async function review(item, status) {
    busyId.value = item.id;

    try {
        const updated = await api.patch(`/api/limit-requests/${item.id}`, { status, note: notes[item.id] || null });
        requests.value = requests.value.map((row) => (row.id === item.id ? updated : row));
        show(status === 'disetujui' ? 'Limit ditambah' : 'Pengajuan ditolak');
    } catch (error) {
        show(error.message);
    } finally {
        busyId.value = null;
    }
}
</script>

<template>
    <AdminShell>
        <div class="goods-tabs stage-tabs">
            <button type="button" class="admin-tab" :class="{ active: tab === 'menunggu' }" @click="tab = 'menunggu'">
                Menunggu HR<b v-if="pendingCount" class="tab-count">{{ pendingCount }}</b>
            </button>
            <button type="button" class="admin-tab" :class="{ active: tab === 'riwayat' }" @click="tab = 'riwayat'">Riwayat</button>
        </div>
        <section class="table-card">
            <div v-if="loading" class="empty">Memuat pengajuan...</div>
            <div v-else-if="visible.length === 0" class="empty">Tidak ada pengajuan.</div>
            <div v-else class="table-scroll">
                <table class="data-table order-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Karyawan</th>
                            <th>Alasan & atasan</th>
                            <th>Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in visible" :key="item.id">
                            <td>
                                <div class="product-cell">
                                    <ProductPhoto :src="item.image_url || ''" :alt="item.product_name" />
                                    <div>
                                        <strong>{{ item.product_name }}</strong>
                                        <small class="claim-mini">{{ item.brand }} · +{{ item.extra_qty }} pcs · tahun {{ item.year }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ item.user?.name }}<br><small class="claim-mini">{{ item.user?.team }} · {{ item.created_label }}</small></td>
                            <td class="reason-cell">
                                {{ item.reason }}
                                <small class="claim-mini">Atasan: {{ item.supervisor_name }} · {{ item.supervisor_email }}</small>
                            </td>
                            <td>
                                <div v-if="item.status === 'menunggu'" class="claim-actions action-row">
                                    <input v-model="notes[item.id]" class="inline-input" type="text" maxlength="255" placeholder="Catatan (opsional)">
                                    <button type="button" :disabled="busyId === item.id" @click="review(item, 'disetujui')">Setujui</button>
                                    <button class="danger" type="button" :disabled="busyId === item.id" @click="review(item, 'ditolak')">Tolak</button>
                                </div>
                                <div v-else class="claim-actions">
                                    <span class="status" :class="{ off: item.status === 'ditolak' }">{{ item.status_label }}</span>
                                    <small v-if="item.review_note" class="claim-mini">{{ item.review_note }}</small>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminShell>
</template>
