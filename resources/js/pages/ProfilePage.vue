<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import AppHeader from '../components/AppHeader.vue';
import AppSelect from '../components/AppSelect.vue';
import ClaimBoard from '../components/ClaimBoard.vue';
import ProductPhoto from '../components/ProductPhoto.vue';
import { changePassword, user } from '../session';
import { useToast } from '../toast';

const { show } = useToast();
const saving = ref(false);
const error = ref('');
const currentPassword = ref('');
const nextPassword = ref('');
const confirmPassword = ref('');

const limits = ref(null);
const catalog = ref(null);
const limitError = ref('');
const requestError = ref('');
const sending = ref(false);
const request = ref(blankRequest());
const productOptions = computed(() => (catalog.value?.brands ?? []).flatMap((brand) => brand.products.map((product) => ({
    value: product.id,
    label: `${brand.short} · ${product.name}`,
}))));

function blankRequest() {
    return { product_id: '', extra_qty: 1, reason: '', supervisor_name: '', supervisor_email: '' };
}

onMounted(async () => {
    try {
        [limits.value, catalog.value] = await Promise.all([
            api.get('/api/limits'),
            api.get('/api/catalog'),
        ]);
    } catch (caught) {
        limitError.value = caught.message;
    }
});

async function sendRequest() {
    requestError.value = '';
    sending.value = true;

    try {
        const created = await api.post('/api/limit-requests', {
            ...request.value,
            product_id: Number(request.value.product_id) || null,
            extra_qty: Number(request.value.extra_qty) || 0,
        });
        limits.value.requests = [created, ...limits.value.requests];
        request.value = blankRequest();
        show('Pengajuan limit terkirim ke HR.');
    } catch (caught) {
        requestError.value = caught.message;
    } finally {
        sending.value = false;
    }
}

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
                    <p>Data akun, progres pesanan, dan kuota pembelian kamu.</p>
                </div>
            </div>

            <ClaimBoard />

            <section class="limit-section">
                <div class="section-title">
                    <h2>Kuota pembelian {{ limits?.year }}</h2>
                    <p>Maks. {{ limits?.per_sku ?? 5 }} pcs per SKU per tahun. Butuh lebih? Ajukan penambahan limit dengan persetujuan atasan.</p>
                </div>
                <p v-if="limitError" class="form-error">{{ limitError }}</p>
                <div class="limit-grid">
                    <div class="table-card">
                        <div class="table-scroll">
                            <table class="data-table compact-table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Terpakai</th>
                                        <th>Kuota</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!limits?.products?.length">
                                        <td colspan="4">Belum ada pembelian tahun ini.</td>
                                    </tr>
                                    <tr v-for="row in limits?.products ?? []" :key="row.product_id">
                                        <td>
                                            <div class="product-cell">
                                                <ProductPhoto :src="row.image_url || ''" :alt="row.name" />
                                                <span>{{ row.name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ row.used }}</td>
                                        <td>{{ row.allowed }}</td>
                                        <td><span class="status" :class="{ off: row.remaining === 0 }">{{ row.remaining }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <ul v-if="limits?.requests?.length" class="request-list">
                            <li v-for="item in limits.requests" :key="item.id">
                                <div>
                                    <strong>{{ item.product_name }} +{{ item.extra_qty }} pcs</strong>
                                    <small>{{ item.created_label }} · atasan {{ item.supervisor_name }}<template v-if="item.review_note"> · {{ item.review_note }}</template></small>
                                </div>
                                <span class="status" :class="{ off: item.status === 'ditolak', warn: item.status === 'menunggu' }">{{ item.status_label }}</span>
                            </li>
                        </ul>
                    </div>
                    <form class="editor" @submit.prevent="sendRequest">
                        <h2>Form penambahan limit</h2>
                        <label class="field">
                            <span>Produk</span>
                            <AppSelect v-model="request.product_id" :options="productOptions" placeholder="Pilih produk" />
                        </label>
                        <label class="field">
                            <span>Tambahan (pcs)</span>
                            <input v-model.number="request.extra_qty" type="number" min="1" max="20" required>
                        </label>
                        <label class="field">
                            <span>Alasan</span>
                            <textarea v-model="request.reason" rows="3" minlength="10" maxlength="1000" required></textarea>
                        </label>
                        <label class="field">
                            <span>Nama atasan</span>
                            <input v-model="request.supervisor_name" type="text" maxlength="120" required>
                        </label>
                        <label class="field">
                            <span>Email atasan</span>
                            <input v-model="request.supervisor_email" type="email" maxlength="255" required>
                        </label>
                        <p v-if="requestError" class="form-error">{{ requestError }}</p>
                        <div class="editor-actions">
                            <button class="primary-btn" type="submit" :disabled="sending">{{ sending ? 'Mengirim...' : 'Ajukan ke HR' }}</button>
                        </div>
                    </form>
                </div>
            </section>

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
        </main>
    </div>
</template>
