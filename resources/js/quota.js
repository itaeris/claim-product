import { ref } from 'vue';
import { api } from './api';

export const quota = ref(null);

let pending = null;

export function loadQuota(force = false) {
    if (quota.value && !force) {
        return Promise.resolve(quota.value);
    }

    pending ??= api.get('/api/limits')
        .then((data) => {
            quota.value = data;

            return data;
        })
        .catch(() => quota.value)
        .finally(() => {
            pending = null;
        });

    return pending;
}

export function clearQuota() {
    quota.value = null;
}

const brandColors = {
    AB: '#b8925a',
    FTI: '#2f7258',
    'K+A': '#8c3f4a',
};

export function brandColor(short) {
    return brandColors[short] ?? '#5f6d66';
}
