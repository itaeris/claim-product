<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { adminBrandId, loadAdminBrands } from '../adminBrand';
import { api } from '../api';
import { rupiah } from '../format';
import { useToast } from '../toast';
import AdminShell from '../components/AdminShell.vue';
import AppSelect from '../components/AppSelect.vue';
import ProductPhoto from '../components/ProductPhoto.vue';

const { show } = useToast();
const brands = ref([]);
const products = ref([]);
const form = ref(null);
const query = ref('');
const saving = ref(false);
const loading = ref(true);
const discount = computed(() => brands.value.find((brand) => brand.id === Number(form.value?.brand_id))?.discount_percent ?? 0);
const imageFile = ref(null);
const imagePreview = ref('');
const removeImage = ref(false);
const fileInput = ref(null);

const shownImage = computed(() => imagePreview.value || (removeImage.value ? '' : form.value?.image_url || ''));

const claimPreview = computed(() => Math.round(Number(form.value?.retail_price || 0) * (100 - discount.value) / 100));

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    return products.value.filter((product) => {
        const haystack = `${product.name} ${product.category_name}`.toLowerCase();

        return product.brand_id === adminBrandId.value && haystack.includes(term);
    });
});

const categoryOptions = computed(() => brands.value.find((brand) => brand.id === Number(form.value?.brand_id))?.categories ?? []);

watch(adminBrandId, () => {
    form.value = null;
});

watch(() => form.value?.brand_id, () => {
    if (!form.value) {
        return;
    }

    if (!categoryOptions.value.some((category) => category.id === Number(form.value.category_id))) {
        form.value.category_id = categoryOptions.value[0]?.id ?? '';
    }
});

async function load() {
    const [brandList, productList] = await Promise.all([
        loadAdminBrands(),
        api.get('/api/products'),
    ]);
    brands.value = brandList;
    products.value = productList;
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

function clearImagePreview() {
    if (imagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imageFile.value = null;
    imagePreview.value = '';
    removeImage.value = false;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function openCreate() {
    const brand = brands.value.find((item) => item.id === adminBrandId.value) ?? brands.value[0];
    clearImagePreview();
    form.value = {
        id: null,
        brand_id: brand?.id ?? '',
        category_id: brand?.categories[0]?.id ?? '',
        name: '',
        retail_price: '',
        image_url: '',
        is_active: true,
    };
}

function openEdit(product) {
    clearImagePreview();
    form.value = { ...product };
}

function onImage(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    if (imagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imageFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
    removeImage.value = false;
}

function clearImage() {
    clearImagePreview();
    removeImage.value = true;
}

onBeforeUnmount(() => {
    if (imagePreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(imagePreview.value);
    }
});

async function save() {
    saving.value = true;
    const payload = new FormData();
    payload.append('brand_id', form.value.brand_id);
    payload.append('category_id', form.value.category_id);
    payload.append('name', form.value.name);
    payload.append('retail_price', form.value.retail_price);
    payload.append('is_active', form.value.is_active ? '1' : '0');

    if (imageFile.value) {
        payload.append('image', imageFile.value);
    }

    if (removeImage.value) {
        payload.append('remove_image', '1');
    }

    try {
        if (form.value.id) {
            await api.form(`/api/products/${form.value.id}`, payload, 'PATCH');
            show('Produk diperbarui');
        } else {
            await api.form('/api/products', payload);
            show('Produk ditambahkan');
        }
        form.value = null;
        await load();
    } catch (error) {
        show(error.message);
    } finally {
        saving.value = false;
    }
}

async function remove(product) {
    if (!window.confirm(`Hapus produk ${product.name}?`)) {
        return;
    }

    try {
        const result = await api.delete(`/api/products/${product.id}`);
        show(result.message);
        if (form.value?.id === product.id) {
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
                <input v-model="query" type="search" placeholder="Cari produk...">
            </label>
            <button class="primary-btn" type="button" @click="openCreate">Produk baru</button>
        </div>
        <div class="admin-layout" :class="{ 'has-editor': form }">
            <section class="table-card">
                <div v-if="loading" class="empty">Memuat produk...</div>
                <div v-else-if="filtered.length === 0" class="empty">Produk tidak ditemukan.</div>
                <div v-else class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Harga retail</th>
                                <th>Harga klaim</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in filtered" :key="product.id">
                                <td>
                                    <div class="product-cell">
                                        <ProductPhoto :src="product.image_url || ''" :alt="product.name" />
                                        <span>{{ product.name }}</span>
                                    </div>
                                </td>
                                <td>{{ product.category_name }}</td>
                                <td>{{ rupiah(product.retail_price) }}</td>
                                <td>{{ rupiah(product.claim_price) }}</td>
                                <td><span class="status" :class="{ off: !product.is_active }">{{ product.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="row-actions">
                                    <button type="button" @click="openEdit(product)">Ubah</button>
                                    <button type="button" class="danger" @click="remove(product)">Hapus</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <form v-if="form" class="editor" @submit.prevent="save">
                <h2>{{ form.id ? 'Ubah produk' : 'Produk baru' }}</h2>
                <label class="field">
                    <span>Brand</span>
                    <AppSelect v-model="form.brand_id" :options="brands.map((brand) => ({ value: brand.id, label: brand.name }))" />
                </label>
                <label class="field">
                    <span>Kategori</span>
                    <AppSelect v-model="form.category_id" :options="categoryOptions.map((category) => ({ value: category.id, label: category.name }))" />
                </label>
                <label class="field">
                    <span>Nama</span>
                    <input v-model="form.name" required maxlength="180" placeholder="Nama produk">
                </label>
                <label class="field">
                    <span>Harga retail (IDR)</span>
                    <div class="money">
                        <b>Rp</b>
                        <input v-model="form.retail_price" type="number" min="0" required placeholder="69000">
                    </div>
                    <small class="currency-note">Harga klaim {{ rupiah(claimPreview) }} setelah diskon {{ discount }}%.</small>
                </label>
                <div class="field">
                    <span>Gambar</span>
                    <button class="image-picker" type="button" @click="fileInput?.click()">
                        <ProductPhoto :src="shownImage" alt="Pratinjau gambar produk" />
                    </button>
                    <input ref="fileInput" class="image-file" type="file" accept="image/jpeg,image/png,image/webp" @change="onImage">
                    <div class="image-picker-actions">
                        <button class="text-btn" type="button" @click="fileInput?.click()">Lampirkan gambar</button>
                        <button v-if="shownImage" class="text-btn danger" type="button" @click="clearImage">Hapus gambar</button>
                    </div>
                </div>
                <label class="check">
                    <input v-model="form.is_active" type="checkbox">
                    <span>Tampil di katalog</span>
                </label>
                <div class="editor-actions">
                    <button class="primary-btn" type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
                    <button class="ghost-btn" type="button" @click="form = null">Batal</button>
                </div>
            </form>
        </div>
    </AdminShell>
</template>
