<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../api';
import { brandLogo } from '../brandLogo';
import { awaitingPayment, claimSteps, stepState, statusTone } from '../claims';
import { rupiah } from '../format';
import { useToast } from '../toast';
import ProductPhoto from './ProductPhoto.vue';

const props = defineProps({
    claim: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['updated']);
const { show } = useToast();
const amount = ref(props.claim.remaining_due || '');
const proof = ref(null);
const fileInput = ref(null);
const busy = ref(false);
const error = ref('');
const steps = computed(() => claimSteps(props.claim));
const canPay = computed(() => awaitingPayment.includes(props.claim.status));
const canConfirm = computed(() => ['dikirim', 'siap_diambil'].includes(props.claim.status));
const bankReady = computed(() => props.claim.bank?.bank_name && props.claim.bank?.account_number);
const lastRejected = computed(() => [...props.claim.payments].reverse().find((payment) => payment.status === 'ditolak'));

watch(() => props.claim.remaining_due, (due) => {
    amount.value = due || '';
});

function pickFile(event) {
    proof.value = event.target.files?.[0] ?? null;
}

async function uploadProof() {
    if (!proof.value) {
        error.value = 'Pilih file bukti transfer dulu.';

        return;
    }

    const form = new FormData();
    form.append('amount', String(amount.value || 0));
    form.append('proof', proof.value);
    busy.value = true;
    error.value = '';

    try {
        emit('updated', await api.form(`/api/claims/${props.claim.id}/payments`, form));
        proof.value = null;

        if (fileInput.value) {
            fileInput.value.value = '';
        }

        show('Bukti transfer terkirim. Finance akan memverifikasi.');
    } catch (caught) {
        error.value = caught.message;
    } finally {
        busy.value = false;
    }
}

async function confirmReceived() {
    busy.value = true;

    try {
        emit('updated', await api.post(`/api/claims/${props.claim.id}/received`));
        show('Terima kasih! Pesanan ditandai selesai.');
    } catch (caught) {
        show(caught.message);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <article class="claim-card">
        <div class="claim-card-head">
            <div class="claim-product">
                <span class="cart-group-logo">
                    <img v-if="brandLogo(claim.brand)" :src="brandLogo(claim.brand)" :alt="claim.brand.name">
                    <template v-else>{{ claim.brand.short }}</template>
                </span>
                <div>
                    <strong>{{ claim.invoice_number || claim.number }}</strong>
                    <small>{{ claim.brand.company_name }} · {{ claim.submitted_label }} · {{ claim.fulfillment === 'delivery' ? 'Dikirim' : 'Ambil di gudang' }}</small>
                </div>
            </div>
            <span class="status" :class="statusTone(claim.status)">{{ claim.status_label }}</span>
        </div>

        <ol class="goods-track" :aria-label="`Progres ${claim.invoice_number}`">
            <li v-for="(step, index) in steps" :key="step.id" :class="stepState(claim, index)">{{ step.label }}</li>
        </ol>

        <ul class="claim-lines">
            <li v-for="item in claim.items" :key="item.product_id">
                <span class="product-cell">
                    <ProductPhoto :src="item.image_url || ''" :alt="item.name" />
                    <span>{{ item.name }} × {{ item.quantity }}</span>
                </span>
                <strong>{{ rupiah(item.unit_claim_price * item.quantity) }}</strong>
            </li>
            <li><span>Diskon {{ claim.discount_percent }}% dari RSP</span><span>− {{ rupiah(claim.saving_total) }}</span></li>
            <li><span>Ongkir</span><span>{{ claim.fulfillment === 'delivery' ? rupiah(claim.shipping_fee) : 'Gratis (pickup)' }}</span></li>
            <li v-if="claim.paid_total"><span>Sudah dibayar</span><span>{{ rupiah(claim.paid_total) }}</span></li>
        </ul>
        <div class="claim-card-total">
            <span>{{ claim.paid_total && claim.remaining_due ? 'Sisa tagihan' : 'Total tagihan' }}</span>
            <strong>{{ rupiah(claim.paid_total && claim.remaining_due ? claim.remaining_due : claim.total_due) }}</strong>
        </div>

        <div class="claim-next" :class="statusTone(claim.status)">
            <template v-if="claim.status === 'cek_stok'">
                Limit pembelian sudah dicek sistem. CS sedang mengecek stok, invoice dikirim setelah stok tersedia.
            </template>
            <template v-else-if="claim.status === 'stok_kosong'">
                Stok belum tersedia. Kamu akan dikabari begitu barang siap.
            </template>
            <template v-else-if="canPay">
                <p v-if="claim.status === 'kurang_bayar'"><strong>Pembayaran kurang {{ rupiah(claim.remaining_due) }}.</strong> Transfer sisanya lalu unggah bukti baru.</p>
                <p v-else-if="lastRejected"><strong>Bukti sebelumnya ditolak:</strong> {{ lastRejected.note }}</p>
                <div class="bank-box">
                    <small>Transfer ke</small>
                    <template v-if="bankReady">
                        <strong>{{ claim.bank.bank_name }} · {{ claim.bank.account_number }}</strong>
                        <span>a.n. {{ claim.bank.account_name || claim.bank.company_name }}</span>
                    </template>
                    <strong v-else>{{ claim.bank?.company_name }} (rekening belum diisi, hubungi Finance)</strong>
                    <span>Berita transfer: {{ claim.invoice_number }}</span>
                </div>
                <form class="proof-form" @submit.prevent="uploadProof">
                    <label class="money money-compact">
                        <b>Rp</b>
                        <input v-model.number="amount" type="number" min="1" inputmode="numeric" aria-label="Nominal transfer" required>
                    </label>
                    <label class="file-pick">
                        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" @change="pickFile">
                        <span>{{ proof?.name || 'Pilih bukti transfer' }}</span>
                    </label>
                    <button class="primary-btn" type="submit" :disabled="busy">{{ busy ? 'Mengunggah...' : 'Kirim bukti' }}</button>
                </form>
                <p v-if="error" class="form-error">{{ error }}</p>
            </template>
            <template v-else-if="claim.status === 'verifikasi_pembayaran'">
                Bukti transfer sudah diterima. Finance sedang mencocokkan nominal dengan invoice.
            </template>
            <template v-else-if="claim.status === 'disiapkan'">
                Pembayaran sesuai. Gudang sedang menyiapkan barang.
            </template>
            <template v-else-if="claim.status === 'dikirim'">
                Dikirim ke {{ claim.shipping_address }}<template v-if="claim.tracking_number"> · resi {{ claim.tracking_number }}</template>.
            </template>
            <template v-else-if="claim.status === 'siap_diambil'">
                Barang siap diambil di {{ claim.pickup_address }}.
            </template>
            <template v-else-if="claim.status === 'dibatalkan'">
                Pesanan dibatalkan.
            </template>
            <template v-else-if="claim.status === 'selesai'">
                Pesanan selesai{{ claim.completed_label ? ` pada ${claim.completed_label}` : '' }}.
            </template>
            <p v-if="claim.refund_total" class="refund-note">
                Lebih bayar {{ rupiah(claim.refund_total) }} · {{ claim.refunded ? 'sudah dikembalikan Finance' : 'akan dikembalikan Finance' }}
            </p>
        </div>

        <div class="claim-card-actions">
            <a v-if="claim.invoice_url" class="ghost-btn" :href="claim.invoice_url" target="_blank" rel="noopener">Lihat invoice</a>
            <a v-for="(payment, index) in claim.payments" :key="payment.id" class="chip-btn muted" :href="payment.proof_url" target="_blank" rel="noopener">
                Bukti {{ index + 1 }} · {{ payment.status === 'menunggu' ? 'diverifikasi' : payment.status }}
            </a>
            <button v-if="canConfirm" class="primary-btn" type="button" :disabled="busy" @click="confirmReceived">Barang sudah diterima</button>
        </div>
    </article>
</template>
