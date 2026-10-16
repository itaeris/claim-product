import { ref } from 'vue';
import { api, setCsrf } from './api';
import { clearQuota } from './quota';

export const user = ref(null);
export const sessionReady = ref(false);

let pending = null;

export async function loadSession() {
    if (sessionReady.value) {
        return user.value;
    }

    pending ??= api.get('/api/me')
        .then((data) => {
            user.value = data?.email ? data : null;
            sessionReady.value = true;
        })
        .catch((error) => {
            if (error.status !== 401) {
                throw error;
            }

            user.value = null;
            sessionReady.value = true;
        })
        .finally(() => {
            pending = null;
        });

    await pending;

    return user.value;
}

export async function login(payload) {
    const data = await api.post('/login', payload);
    setCsrf(data.csrf);
    clearQuota();
    user.value = data.user;
    sessionReady.value = true;

    return data.user;
}

export async function changePassword(payload) {
    return api.patch('/api/password', payload);
}

export async function logout() {
    const data = await api.post('/logout');
    setCsrf(data.csrf);
    user.value = null;
    clearQuota();
}
