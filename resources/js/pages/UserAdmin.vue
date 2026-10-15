<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../api';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import AppSelect from '../components/AppSelect.vue';

const { show } = useToast();
const users = ref([]);
const form = ref(null);
const query = ref('');
const saving = ref(false);
const loading = ref(true);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return users.value.filter((person) => `${person.name} ${person.email} ${person.team}`.toLowerCase().includes(term));
});

async function load() {
    users.value = await api.get('/api/users');
}

onMounted(async () => {
    try {
        await load();
    } catch (error) {
        show(error.message);
    } finally {
        loading.value = false;
    }
});

function openCreate() {
    form.value = { id: null, name: '', email: '', team: '', role: 'user', password: '' };
}

function openEdit(person) {
    form.value = { id: person.id, name: person.name, email: person.email, team: person.team, role: person.role, password: '' };
}

async function save() {
    saving.value = true;
    const payload = {
        name: form.value.name,
        email: form.value.email,
        team: form.value.team,
        role: form.value.role,
    };

    if (form.value.password) {
        payload.password = form.value.password;
    }

    try {
        if (form.value.id) {
            await api.patch(`/api/users/${form.value.id}`, payload);
            show('User diperbarui');
        } else {
            await api.post('/api/users', payload);
            show('User ditambahkan');
        }
        form.value = null;
        await load();
    } catch (error) {
        show(error.message);
    } finally {
        saving.value = false;
    }
}

async function remove(person) {
    if (!window.confirm(`Hapus user ${person.name}?`)) {
        return;
    }

    try {
        const result = await api.delete(`/api/users/${person.id}`);
        show(result.message);
        if (form.value?.id === person.id) {
            form.value = null;
        }
        await load();
    } catch (error) {
        show(error.message);
    }
}
</script>

<template>
    <AdminShell>
        <div class="admin-toolbar">
            <label class="search">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input v-model="query" type="search" placeholder="Cari user...">
            </label>
            <button class="primary-btn" type="button" @click="openCreate">User baru</button>
        </div>
        <div class="admin-layout" :class="{ 'has-editor': form }">
            <section class="table-card">
                <div v-if="loading" class="empty">Memuat user...</div>
                <div v-else-if="filtered.length === 0" class="empty">User tidak ditemukan.</div>
                <div v-else class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Tim</th>
                                <th>Role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="person in filtered" :key="person.id">
                                <td><span class="product-cell"><span>{{ person.name }}</span></span></td>
                                <td>{{ person.email }}</td>
                                <td>{{ person.team }}</td>
                                <td><span class="status" :class="{ off: person.role !== 'admin' }">{{ person.role === 'admin' ? 'Admin' : 'User' }}</span></td>
                                <td class="row-actions">
                                    <button type="button" @click="openEdit(person)">Ubah</button>
                                    <button type="button" class="danger" @click="remove(person)">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <form v-if="form" class="editor" @submit.prevent="save">
                <h2>{{ form.id ? 'Ubah user' : 'User baru' }}</h2>
                <label class="field">
                    <span>Nama</span>
                    <input v-model="form.name" required maxlength="120" placeholder="Nama lengkap">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input v-model="form.email" type="email" required maxlength="255" placeholder="nama@perusahaan.com">
                </label>
                <label class="field">
                    <span>Tim</span>
                    <input v-model="form.team" required maxlength="120" placeholder="Marketing Team">
                </label>
                <label class="field">
                    <span>Role</span>
                    <AppSelect v-model="form.role" :options="[{ value: 'user', label: 'User' }, { value: 'admin', label: 'Admin' }]" />
                </label>
                <label class="field">
                    <span>{{ form.id ? 'Password baru' : 'Password' }}</span>
                    <input v-model="form.password" type="password" minlength="8" :required="!form.id" :placeholder="form.id ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'">
                </label>
                <div class="editor-actions">
                    <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
                    <button class="ghost-btn" type="button" @click="form = null">Batal</button>
                </div>
            </form>
        </div>
    </AdminShell>
</template>
