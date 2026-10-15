<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../api';
import AppHeader from '../components/AppHeader.vue';
import { goodsSteps, stepState } from '../claims';
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
                <h2>Progres barang</h2>
                <p v-if="loadingClaims" class="empty-note">Memuat progres...</p>
                <p v-else-if="claimsError" class="form-error">{{ claimsError }}</p>
                <article v-else-if="claims.length === 0" class="claim-card">
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
                <article v-for="claim in claims" :key="claim.id" class="claim-card">
                    <div class="claim-card-head">
                        <div>
                            <strong>{{ claim.items.length ? claim.items.map((item) => `${item.name} × ${item.quantity}`).join(', ') : claim.number }}</strong>
                            <small>{{ claim.number }} · {{ claim.submitted_label }}</small>
                        </div>
                        <span v-if="claim.status === 'ditolak'" class="status off">Ditolak</span>
                    </div>
                    <ol class="goods-track" :aria-label="`Progres ${claim.number}`">
                        <li v-for="(step, index) in goodsSteps" :key="step.id" :class="stepState(claim.status, index, goodsSteps)">
                            {{ step.label }}
                        </li>
                    </ol>
                </article>
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
