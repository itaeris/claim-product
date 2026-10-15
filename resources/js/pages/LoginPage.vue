<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { login } from '../session';

const route = useRoute();
const router = useRouter();
const email = ref('');
const password = ref('');
const remember = ref(false);
const showPassword = ref(false);
const error = ref('');
const submitting = ref(false);
const sheetOffset = ref(0);
const dragging = ref(false);
let dragOrigin = 0;
let offsetOrigin = 0;

function startDrag(event) {
    if (!window.matchMedia('(max-width: 900px)').matches) {
        return;
    }

    if (event.button !== undefined && event.button !== 0) {
        return;
    }

    const target = event.target instanceof Element ? event.target : event.target?.parentElement;

    if (!target || target.closest('input, button, a, textarea, .login-remember')) {
        return;
    }

    dragging.value = true;
    dragOrigin = event.clientY;
    offsetOrigin = sheetOffset.value;
    window.addEventListener('pointermove', moveDrag);
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);
}

function moveDrag(event) {
    if (!dragging.value) {
        return;
    }

    const next = offsetOrigin + event.clientY - dragOrigin;
    sheetOffset.value = Math.min(280, Math.max(-160, next));
}

function endDrag() {
    dragging.value = false;
    window.removeEventListener('pointermove', moveDrag);
    window.removeEventListener('pointerup', endDrag);
    window.removeEventListener('pointercancel', endDrag);
}

onBeforeUnmount(endDrag);
const collage = [
    'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1596704017254-9b121068fb31?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=500&h=500&q=70',
    'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=500&h=500&q=70',
];
const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    if (hour < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
});

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
        <section class="login-hero">
            <div class="login-collage" aria-hidden="true">
                <img v-for="photo in collage" :key="photo" :src="photo" alt="">
            </div>
            <div class="brandmark">
                <div class="brandmark-logo">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 12.5 10.2 17 18 7.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 5.5h15v14h-15z" stroke="currentColor" stroke-width="1.5" rx="3"/></svg>
                </div>
                <div>
                    <div class="brandmark-name">claim<span>ly</span></div>
                    <small>Employee Product Claim</small>
                </div>
            </div>
            <div class="login-hero-copy">
                <p class="login-greeting">{{ greeting }}</p>
                <h1>Katalog klaim<br><span>khusus untuk kamu.</span></h1>
                <p>Pilih produk, ajukan klaim, dan pantau progres dalam satu tempat.</p>
                <div class="login-pills">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg>
                        <em>Katalog</em>
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 8h12l-1 11H7L6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <em>Keranjang</em>
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v4.5l3 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <em>Progres</em>
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16M7 7h10M9 17h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <em>Harga karyawan</em>
                    </span>
                </div>
            </div>
            <p class="login-footer">Claimly untuk karyawan</p>
        </section>
        <div
            class="login-stage"
            :class="{ dragging }"
            :style="{ '--sheet-y': `${sheetOffset}px` }"
            @pointerdown="startDrag"
        >
            <div class="login-sheet-handle" aria-hidden="true"></div>
            <form class="login-card" @submit.prevent="submit">
                <h1>Selamat datang</h1>
                <p>Masuk untuk melanjutkan ke Claimly.</p>
                <label class="login-field">
                    <span>Email</span>
                    <div class="login-control">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <input v-model="email" type="email" autocomplete="username" required placeholder="nama@perusahaan.com">
                    </div>
                </label>
                <label class="login-field">
                    <span>Password</span>
                    <div class="login-control">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required placeholder="Password">
                        <button type="button" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">
                            <svg v-if="showPassword" viewBox="0 0 24 24" fill="none"><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M9.9 5.2A9.8 9.8 0 0 1 12 5c5.5 0 9 4.5 9.5 7-.3 1.4-1.2 3-2.6 4.3M6.1 6.7C4.2 8 3 10 2.5 12c.5 2.5 4 7 9.5 7 1.3 0 2.5-.3 3.6-.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <svg v-else viewBox="0 0 24 24" fill="none"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
                        </button>
                    </div>
                </label>
                <label class="login-remember">
                    <input v-model="remember" type="checkbox">
                    <span>Ingat saya</span>
                </label>
                <p v-if="error" class="form-error">{{ error }}</p>
                <button class="login-submit" type="submit" :disabled="submitting">{{ submitting ? 'Memeriksa...' : 'Masuk' }}</button>
            </form>
            <p class="login-footer login-footer-sheet">Claimly untuk karyawan</p>
        </div>
    </div>
</template>
