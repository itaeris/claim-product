<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { adminBrandId, loadAdminBrands } from '../adminBrand';
import { api } from '../api';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import AppSelect from '../components/AppSelect.vue';

const { show } = useToast();
const brands = ref([]);
const categories = ref([]);
const form = ref(null);
const query = ref('');
const saving = ref(false);
const loading = ref(true);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return categories.value.filter((category) => category.brand_id === adminBrandId.value && category.name.toLowerCase().includes(term));
});

watch(adminBrandId, () => {
    form.value = null;
});

async function load() {
    const [brandList, categoryList] = await Promise.all([
        loadAdminBrands(),
        api.get('/api/categories'),
    ]);
    brands.value = brandList;
    categories.value = categoryList;
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

function blankForm() {
    const brandId = adminBrandId.value ?? brands.value[0]?.id ?? '';

    return {
        id: null,
        brand_id: brandId,
        name: '',
        sort_order: categories.value.filter((category) => category.brand_id === brandId).length,
    };
}

function openCreate() {
    form.value = blankForm();
}

function openEdit(category) {
    form.value = {
        id: category.id,
        brand_id: category.brand_id,
        name: category.name,
        sort_order: category.sort_order,
    };
}

async function save() {
    saving.value = true;
    const payload = {
        brand_id: Number(form.value.brand_id),
        name: form.value.name,
        sort_order: Number(form.value.sort_order || 0),
    };

    try {
        if (form.value.id) {
            await api.patch(`/api/categories/${form.value.id}`, payload);
            show('Kategori diperbarui');
        } else {
            await api.post('/api/categories', payload);
            show('Kategori ditambahkan');
        }
        form.value = null;
        await load();
    } catch (error) {
        show(error.message);
    } finally {
        saving.value = false;
    }
}

async function remove(category) {
    if (!window.confirm(`Hapus kategori ${category.name}?`)) {
        return;
    }

    try {
        const result = await api.delete(`/api/categories/${category.id}`);
        show(result.message);
        if (form.value?.id === category.id) {
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
                <input v-model="query" type="search" placeholder="Cari kategori...">
            </label>
            <button class="primary-btn" type="button" @click="openCreate">Kategori baru</button>
        </div>
        <div class="admin-layout" :class="{ 'has-editor': form }">
            <section class="table-card">
                <div v-if="loading" class="empty">Memuat kategori...</div>
                <div v-else-if="filtered.length === 0" class="empty">Kategori tidak ditemukan.</div>
                <div v-else class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Produk</th>
                                <th>Urutan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in filtered" :key="category.id">
                                <td>{{ category.name }}</td>
                                <td>{{ category.products_count }}</td>
                                <td>{{ category.sort_order }}</td>
                                <td class="row-actions">
                                    <button type="button" @click="openEdit(category)">Ubah</button>
                                    <button type="button" class="danger" @click="remove(category)">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <form v-if="form" class="editor" @submit.prevent="save">
                <h2>{{ form.id ? 'Ubah kategori' : 'Kategori baru' }}</h2>
                <label class="field">
                    <span>Brand</span>
                    <AppSelect v-model="form.brand_id" :options="brands.map((brand) => ({ value: brand.id, label: brand.name }))" />
                </label>
                <label class="field">
                    <span>Nama</span>
                    <input v-model="form.name" required maxlength="100" placeholder="Nama kategori">
                </label>
                <label class="field">
                    <span>Urutan</span>
                    <input v-model="form.sort_order" type="number" min="0" max="9999">
                </label>
                <div class="editor-actions">
                    <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
                    <button class="ghost-btn" type="button" @click="form = null">Batal</button>
                </div>
            </form>
        </div>
    </AdminShell>
</template>
