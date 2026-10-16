<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { api } from '../api';
import { brandLogo } from '../brandLogo';
import { statusTone } from '../claims';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';

const { show } = useToast();
const claims = ref([]);
const loading = ref(true);
const stage = ref('cek_stok');
const busyId = ref(null);
const drafts = reactive({});

const needsRefund = (claim) => claim.refund_total > 0 && !claim.refunded;
const stages = [
    { id: 'cek_stok', label: 'Cek stok', hint: 'CS', match: (claim) => ['cek_stok', 'stok_kosong'].includes(claim.status) },
    { id: 'pembayaran', label: 'Menunggu bayar', hint: 'Karyawan', match: (claim) => ['menunggu_pembayaran', 'kurang_bayar'].includes(claim.status) },
    { id: 'verifikasi', label: 'Verifikasi', hint: 'Finance', match: (claim) => claim.status === 'verifikasi_pembayaran' },
    { id: 'gudang', label: 'Gudang', hint: 'Warehouse', match: (claim) => ['disiapkan', 'dikirim', 'siap_diambil'].includes(claim.status) },
    { id: 'refund', label: 'Refund', hint: 'Finance', match: needsRefund },
    { id: 'selesai', label: 'Selesai', match: (claim) => claim.status === 'selesai' },
    { id: 'dibatalkan', label: 'Dibatalkan', match: (claim) => claim.status === 'dibatalkan' },
    { id: 'semua', label: 'Semua', match: () => true },
];
const counts = computed(() => Object.fromEntries(stages.map((item) => [item.id, claims.value.filter(item.match).length])));
const visible = computed(() => claims.value.filter(stages.find((item) => item.id === stage.value).match));

onMounted(async () => {
    try {
        claims.value = await api.get('/api/claims/all');
        claims.value.forEach(prepare);
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

function pendingPayment(claim) {
    return [...claim.payments].reverse().find((payment) => payment.status === 'menunggu') ?? null;
}

function prepare(claim) {
    drafts[claim.id] = {
        amount: pendingPayment(claim)?.declared_amount ?? '',
        note: '',
        tracking: claim.tracking_number ?? '',
    };
}

async function act(claim, action, extra = {}) {
    busyId.value = claim.id;

    try {
        const updated = await api.post(`/api/claims/${claim.id}/actions`, { action, ...extra });
        claims.value = claims.value.map((item) => (item.id === claim.id ? updated : item));
        prepare(updated);
        show(`${updated.invoice_number}: ${updated.status_label}`);
    } catch (error) {
        show(error.message);
    } finally {
        busyId.value = null;
    }
}

function cancel(claim) {
    if (window.confirm(`Batalkan ${claim.invoice_number}?`)) {
        act(claim, 'batalkan', { note: drafts[claim.id].note });
    }
}
</script>

<template>
    <AdminShell>
        <div class="goods-tabs stage-tabs">
            <button
                v-for="item in stages"
                :key="item.id"
                type="button"
                class="admin-tab"
                :class="{ active: stage === item.id }"
                @click="stage = item.id"
            >
                {{ item.label }}<b v-if="counts[item.id]" class="tab-count">{{ counts[item.id] }}</b>
            </button>
        </div>
        <section class="table-card">
            <div v-if="loading" class="empty">Memuat pesanan...</div>
            <div v-else-if="visible.length === 0" class="empty">Tidak ada pesanan di tahap ini.</div>
            <div v-else class="table-scroll">
                <table class="data-table order-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Karyawan</th>
                            <th>Tagihan</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="claim in visible" :key="claim.id">
                            <td>
                                <div class="product-cell">
                                    <span class="cart-group-logo">
                                        <img v-if="brandLogo(claim.brand)" :src="brandLogo(claim.brand)" :alt="claim.brand.name">
                                        <template v-else>{{ claim.brand.short }}</template>
                                    </span>
                                    <div>
                                        <strong>{{ claim.invoice_number || claim.number }}</strong>
                                        <small class="claim-mini">{{ claim.items.map((item) => `${item.name} × ${item.quantity}`).join(', ') }}</small>
                                        <small class="claim-mini">{{ claim.submitted_label }} · {{ claim.fulfillment === 'delivery' ? `Kirim ke ${claim.shipping_address}` : 'Ambil di gudang' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ claim.user?.name }}<br><small class="claim-mini">{{ claim.user?.team }}</small></td>
                            <td class="money-cell">
                                <strong>{{ rupiah(claim.total_due) }}</strong>
                                <small class="claim-mini">Diskon {{ claim.discount_percent }}% · ongkir {{ claim.fulfillment === 'delivery' ? rupiah(claim.shipping_fee) : '—' }}</small>
                                <small v-if="claim.paid_total" class="claim-mini">Dibayar {{ rupiah(claim.paid_total) }}<template v-if="claim.remaining_due"> · kurang {{ rupiah(claim.remaining_due) }}</template></small>
                                <small v-if="claim.refund_total" class="claim-mini">Lebih bayar {{ rupiah(claim.refund_total) }}{{ claim.refunded ? ' · sudah refund' : '' }}</small>
                            </td>
                            <td>
                                <div class="claim-actions">
                                    <span class="status" :class="statusTone(claim.status)">{{ claim.status_label }}</span>
                                    <a v-if="claim.invoice_url" class="chip-btn" :href="claim.invoice_url" target="_blank" rel="noopener">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l4 4v14H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v4h4M10 12h5M10 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                        Invoice
                                    </a>
                                    <a v-for="(payment, index) in claim.payments" :key="payment.id" class="chip-btn muted" :href="payment.proof_url" target="_blank" rel="noopener">
                                        Bukti {{ index + 1 }} ({{ rupiah(payment.declared_amount) }}{{ payment.status === 'ditolak' ? ', ditolak' : '' }})
                                    </a>
                                </div>
                                <div class="claim-actions action-row">
                                    <template v-if="['cek_stok', 'stok_kosong'].includes(claim.status)">
                                        <button type="button" :disabled="busyId === claim.id" @click="act(claim, 'stok_tersedia')">Stok ada · kirim invoice</button>
                                        <button v-if="claim.status === 'cek_stok'" type="button" :disabled="busyId === claim.id" @click="act(claim, 'stok_kosong')">Stok kosong</button>
                                    </template>
                                    <template v-if="claim.status === 'verifikasi_pembayaran'">
                                        <label class="money money-compact">
                                            <b>Rp</b>
                                            <input v-model.number="drafts[claim.id].amount" type="number" min="0" aria-label="Nominal diterima">
                                        </label>
                                        <button type="button" :disabled="busyId === claim.id" @click="act(claim, 'verifikasi', { amount: Number(drafts[claim.id].amount) || 0 })">Cocokkan</button>
                                        <input v-model="drafts[claim.id].note" class="inline-input" type="text" maxlength="255" placeholder="Alasan tolak">
                                        <button class="danger" type="button" :disabled="busyId === claim.id || !drafts[claim.id].note" @click="act(claim, 'tolak_bukti', { note: drafts[claim.id].note })">Tolak bukti</button>
                                    </template>
                                    <template v-if="claim.status === 'disiapkan'">
                                        <input v-if="claim.fulfillment === 'delivery'" v-model="drafts[claim.id].tracking" class="inline-input" type="text" maxlength="100" placeholder="No. resi (opsional)">
                                        <button type="button" :disabled="busyId === claim.id" @click="act(claim, 'kirim', { tracking_number: drafts[claim.id].tracking })">
                                            {{ claim.fulfillment === 'delivery' ? 'Kirim barang' : 'Siap diambil' }}
                                        </button>
                                    </template>
                                    <button v-if="['dikirim', 'siap_diambil'].includes(claim.status)" type="button" :disabled="busyId === claim.id" @click="act(claim, 'selesai')">Sudah diterima</button>
                                    <button v-if="needsRefund(claim)" type="button" :disabled="busyId === claim.id" @click="act(claim, 'refund')">Refund {{ rupiah(claim.refund_total) }} selesai</button>
                                    <button v-if="['cek_stok', 'stok_kosong', 'menunggu_pembayaran'].includes(claim.status) && !claim.paid_total" class="danger" type="button" :disabled="busyId === claim.id" @click="cancel(claim)">Batalkan</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AdminShell>
</template>
