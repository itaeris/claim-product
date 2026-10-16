<script setup>
import { ref } from 'vue';
import ProfileShell from '../components/ProfileShell.vue';
import { changePassword, user } from '../session';
import { useToast } from '../toast';

const { show } = useToast();
const saving = ref(false);
const error = ref('');
const currentPassword = ref('');
const nextPassword = ref('');
const confirmPassword = ref('');

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
    <ProfileShell description="Data akun dan keamanan login kamu.">
        <section class="profile-card">
            <div class="profile-identity">
                <span class="avatar">{{ user?.initials || '—' }}</span>
                <div>
                    <strong>{{ user?.name || 'Karyawan' }}</strong>
                    <small>{{ user?.email }}</small>
                    <small>{{ user?.is_admin ? 'Admin' : 'Karyawan' }}<template v-if="user?.team"> · {{ user.team }}</template></small>
                    <small v-if="user?.employment_label || user?.company">{{ [user?.employment_label, user?.company].filter(Boolean).join(' · ') }}</small>
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
    </ProfileShell>
</template>
