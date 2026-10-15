<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';

const { show } = useToast();
const period = ref(null);
const percent = ref(0);
const sample = ref(100000);
const saving = ref(false);
const loading = ref(true);

const claimSample = computed(() => Math.round(Number(sample.value || 0) * (100 - Number(percent.value || 0)) / 100));

onMounted(async () => {
    try {
        period.value = await api.get('/api/discount');
        percent.value = period.value.discount_percent;
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

async function save() {
    saving.value = true;

    try {
        period.value = await api.patch('/api/discount', { discount_percent: Number(percent.value) });
        percent.value = period.value.discount_percent;
        show('Diskon diperbarui');
    } catch (error) {
        show(error.message);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <AdminShell>
        <form v-if="period" class="editor discount-card" @submit.prevent="save">
            <h2>{{ period.name }}</h2>
            <p class="currency-note">Harga klaim dihitung otomatis dalam Rupiah (IDR) dari harga retail dikurangi diskon ini.</p>
            <label class="field">
                <span>Diskon karyawan</span>
                <div class="money">
                    <input v-model="percent" type="number" min="0" max="100" required>
                    <b>%</b>
                </div>
            </label>
            <label class="field">
                <span>Contoh harga retail</span>
                <div class="money">
                    <b>Rp</b>
                    <input v-model="sample" type="number" min="0">
                </div>
            </label>
            <p class="claim-preview">Harga klaim: <strong>{{ rupiah(claimSample) }}</strong></p>
            <div class="editor-actions">
                <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan diskon' }}</button>
            </div>
        </form>
        <p v-else-if="loading" class="empty-note">Memuat diskon...</p>
    </AdminShell>
</template>
