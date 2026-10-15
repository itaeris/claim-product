<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { login } from '../session';

const route = useRoute();
const router = useRouter();
const email = ref('');
const password = ref('');
const remember = ref(false);
const error = ref('');
const submitting = ref(false);

async function submit() {
    error.value = '';
    submitting.value = true;

    try {
        await login({
            email: email.value,
            password: password.value,
            remember: remember.value,
        });
        await router.push(typeof route.query.redirect === 'string' ? route.query.redirect : '/');
    } catch (caught) {
        error.value = caught.message;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="login-screen">
        <form class="login-card" @submit.prevent="submit">
            <div class="brandmark">
                <div class="brandmark-logo">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 12.5 10.2 17 18 7.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 5.5h15v14h-15z" stroke="currentColor" stroke-width="1.5" rx="3"/></svg>
                </div>
                <div class="brandmark-name">claim<span>ly</span></div>
            </div>
            <h1>Masuk ke akun karyawan</h1>
            <p>Katalog klaim dan progres hanya bisa dibuka setelah masuk.</p>
            <label class="field">
                <span>Email</span>
                <input v-model="email" type="email" autocomplete="username" required placeholder="nama@perusahaan.com">
            </label>
            <label class="field">
                <span>Password</span>
                <input v-model="password" type="password" autocomplete="current-password" required placeholder="Password">
            </label>
            <label class="check">
                <input v-model="remember" type="checkbox">
                <span>Ingat saya</span>
            </label>
            <p v-if="error" class="form-error">{{ error }}</p>
            <button class="claim-btn" type="submit" :disabled="submitting">{{ submitting ? 'Memeriksa...' : 'Masuk' }}</button>
        </form>
    </div>
</template>
