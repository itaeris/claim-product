<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../api';
import { adminBrandId, adminBrands, replaceAdminBrand } from '../adminBrand';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';

const { show } = useToast();
const form = ref(null);
const sample = ref(100000);
const saving = ref(false);
const brand = computed(() => adminBrands.value.find((item) => item.id === adminBrandId.value) ?? null);
const claimSample = computed(() => Math.round(Number(sample.value || 0) * (100 - Number(form.value?.discount_percent || 0)) / 100));

watch(brand, (current) => {
    form.value = current
        ? {
            discount_percent: current.discount_percent,
            shipping_fee: current.shipping_fee,
            company_name: current.company_name || '',
            bank_name: current.bank_name || '',
            bank_account_number: current.bank_account_number || '',
            bank_account_name: current.bank_account_name || '',
        }
        : null;
}, { immediate: true });

async function save() {
    saving.value = true;

    try {
        const updated = await api.patch(`/api/brands/${brand.value.id}/terms`, {
            ...form.value,
            discount_percent: Number(form.value.discount_percent),
            shipping_fee: Number(form.value.shipping_fee),
        });
        replaceAdminBrand(updated);
        show(`Ketentuan ${updated.name} diperbarui`);
    } catch (error) {
        show(error.message);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AdminShell>
        <form v-if="form && brand" class="editor discount-card terms-card" @submit.prevent="save">
            <h2>{{ brand.name }}</h2>
            <p class="currency-note">Diskon, ongkir, dan rekening ini dipakai otomatis di invoice {{ brand.short }}.</p>
            <div class="terms-grid">
                <label class="field">
                    <span>Diskon karyawan (dari RSP)</span>
                    <div class="money">
                        <input v-model="form.discount_percent" type="number" min="0" max="100" required>
                        <b>%</b>
                    </div>
                </label>
                <label class="field">
                    <span>Ongkir per invoice</span>
                    <div class="money">
                        <b>Rp</b>
                        <input v-model="form.shipping_fee" type="number" min="0" required>
                    </div>
                </label>
            </div>
            <label class="field">
                <span>Contoh harga RSP</span>
                <div class="money">
                    <b>Rp</b>
                    <input v-model="sample" type="number" min="0">
                </div>
            </label>
            <p class="claim-preview">Harga karyawan: <strong>{{ rupiah(claimSample) }}</strong></p>

            <h3 class="terms-sub">Rekening tujuan transfer</h3>
            <label class="field">
                <span>Nama PT</span>
                <input v-model="form.company_name" type="text" maxlength="160" required>
            </label>
            <div class="terms-grid">
                <label class="field">
                    <span>Bank</span>
                    <input v-model="form.bank_name" type="text" maxlength="80" placeholder="mis. BCA">
                </label>
                <label class="field">
                    <span>No. rekening</span>
                    <input v-model="form.bank_account_number" type="text" inputmode="numeric" maxlength="40">
                </label>
            </div>
            <label class="field">
                <span>Atas nama</span>
                <input v-model="form.bank_account_name" type="text" maxlength="160">
            </label>
            <p v-if="!form.bank_account_number" class="form-error">Rekening belum diisi. Karyawan akan diminta menghubungi Finance.</p>
            <div class="editor-actions">
                <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan ketentuan' }}</button>
            </div>
        </form>
        <p v-else class="empty-note">Memuat brand...</p>
    </AdminShell>
</template>
