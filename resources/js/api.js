function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function setCsrf(token) {
    const meta = document.querySelector('meta[name="csrf-token"]');

    if (meta && token) {
        meta.setAttribute('content', token);
    }
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = Object.values(data.errors ?? {}).flat()[0];
        const error = new Error(firstError || data.message || 'Terjadi kesalahan.');
        error.status = response.status;
        error.errors = data.errors ?? {};
        throw error;
    }

    return data;
}

async function sendForm(url, formData, method = 'POST') {
    if (method !== 'POST') {
        formData.append('_method', method);
    }

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: formData,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = Object.values(data.errors ?? {}).flat()[0];
        const error = new Error(firstError || data.message || 'Terjadi kesalahan.');
        error.status = response.status;
        error.errors = data.errors ?? {};
        throw error;
    }

    return data;
}

export const api = {
    get: (url) => request(url),
    post: (url, body) => request(url, { method: 'POST', body: JSON.stringify(body ?? {}) }),
    patch: (url, body) => request(url, { method: 'PATCH', body: JSON.stringify(body ?? {}) }),
    delete: (url) => request(url, { method: 'DELETE' }),
    form: (url, formData, method = 'POST') => sendForm(url, formData, method),
};
