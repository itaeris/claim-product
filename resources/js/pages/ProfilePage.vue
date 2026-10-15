<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import AppHeader from '../components/AppHeader.vue';
import ProductPhoto from '../components/ProductPhoto.vue';
import { goodsSteps, stepState } from '../claims';
import { rupiah } from '../format';
import { changePassword, user } from '../session';
import { useToast } from '../toast';

const { show } = useToast();
const claims = ref([]);
const loadingClaims = ref(true);
const claimsError = ref('');
const saving = ref(false);
const error = ref('');
const currentPassword = ref('');
const nextPassword = ref('');
const confirmPassword = ref('');
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
        claimsError.value = caught.message;
    } finally {
        loadingClaims.value = false;
    }
});

async function savePassword() {
    error.value = '';
    saving.value = true;

    try {
        await changePassword({
            current_password: currentPassword.value,
            password: nextPassword.value,
            password_confirmation: confirmPassword.value,
        });
        currentPassword.value = '';
        nextPassword.value = '';
        confirmPassword.value = '';
        show('Password berhasil diubah.');
    } catch (caught) {
        error.value = caught.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <main class="admin-main">
            <div class="admin-heading">
                <div>
                    <h1>Profil</h1>
                    <p>Data akun dan password kamu.</p>
                </div>
            </div>
            <section class="goods-board">
                <div class="goods-tabs">
                    <button type="button" class="admin-tab" :class="{ active: goodsTab === 'progres' }" @click="goodsTab = 'progres'">Progres</button>
                    <button type="button" class="admin-tab" :class="{ active: goodsTab === 'selesai' }" @click="goodsTab = 'selesai'">Selesai</button>
                </div>
                <p v-if="loadingClaims" class="empty-note">Memuat progres...</p>
                <p v-else-if="claimsError" class="form-error">{{ claimsError }}</p>
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
                <article v-else-if="activeClaims.length === 0" class="claim-card">
                    <div class="claim-card-head">
                        <div>
                            <strong>Belum ada barang</strong>
                            <small>Progres akan terisi setelah kamu mengajukan klaim.</small>
                        </div>
                    </div>
                    <ol class="goods-track" aria-label="Progres barang kosong">
                        <li v-for="step in goodsSteps" :key="step.id">{{ step.label }}</li>
                    </ol>
                </article>
                <template v-else>
                <article v-for="claim in activeClaims" :key="claim.id" class="claim-card">
                    <div class="claim-card-head">
                        <div class="claim-product">
                            <div class="claim-photos">
                                <ProductPhoto v-for="(item, index) in claim.items" :key="`${claim.id}-${index}`" :src="item.image_url || ''" :alt="item.name" />
                            </div>
                            <div>
                                <strong>{{ claim.items.length ? claim.items.map((item) => `${item.name} × ${item.quantity}`).join(', ') : claim.number }}</strong>
                                <small>{{ claim.number }} · {{ claim.submitted_label }}</small>
                            </div>
                        </div>
                        <span v-if="claim.status === 'ditolak'" class="status off">Ditolak</span>
                    </div>
                    <ol class="goods-track" :aria-label="`Progres ${claim.number}`">
                        <li v-for="(step, index) in goodsSteps" :key="step.id" :class="stepState(claim.status, index, goodsSteps)">
                            {{ step.label }}
                        </li>
                    </ol>
                </article>
                </template>
            </section>
            <section class="profile-card">
                <div class="profile-identity">
                    <span class="avatar">{{ user?.initials || '—' }}</span>
                    <div>
                        <strong>{{ user?.name || 'Karyawan' }}</strong>
                        <small>{{ user?.email }}</small>
                        <small>{{ user?.is_admin ? 'Admin' : 'Karyawan' }}<template v-if="user?.team"> · {{ user.team }}</template></small>
                    </div>
                </div>
                <form @submit.prevent="savePassword">
                    <h2>Ganti password</h2>
                    <p>Password baru minimal 8 karakter.</p>
                    <label class="field">
                        <span>Password saat ini</span>
                        <input v-model="currentPassword" type="password" autocomplete="current-password" required>
                    </label>
                    <label class="field">
                        <span>Password baru</span>
                        <input v-model="nextPassword" type="password" autocomplete="new-password" minlength="8" required>
                    </label>
                    <label class="field">
                        <span>Ulangi password baru</span>
                        <input v-model="confirmPassword" type="password" autocomplete="new-password" minlength="8" required>
                    </label>
                    <p v-if="error" class="form-error">{{ error }}</p>
                    <div class="password-actions">
                        <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</template>
