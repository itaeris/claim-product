import { ref } from 'vue';
import { api } from './api';

export const adminBrands = ref([]);
export const adminBrandId = ref(null);

let request = null;

export function loadAdminBrands() {
    if (adminBrands.value.length > 0) {
        return Promise.resolve(adminBrands.value);
    }

    request ??= api.get('/api/brands').then((brands) => {
        adminBrands.value = brands;

        if (!brands.some((brand) => brand.id === adminBrandId.value)) {
            adminBrandId.value = brands[0]?.id ?? null;
        }

        return brands;
    }).finally(() => {
        request = null;
    });

    return request;
}

export function selectAdminBrand(id) {
    adminBrandId.value = id;
}

export function replaceAdminBrand(updated) {
    adminBrands.value = adminBrands.value.map((brand) => (brand.id === updated.id ? { ...brand, ...updated } : brand));
}
