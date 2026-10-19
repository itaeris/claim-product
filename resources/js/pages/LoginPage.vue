<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BrandMark from '../components/BrandMark.vue';
import { login } from '../session';
import { homePath } from '../viewMode';

const route = useRoute();
const router = useRouter();
const email = ref('');
const password = ref('');
const remember = ref(false);
const showPassword = ref(false);
const brandMarks = [
    { name: 'AERIS', src: '/logo-brand/marks/aeris.png' },
    { name: 'FTI', src: '/logo-brand/marks/fti.png' },
    { name: 'Kinandally', src: '/logo-brand/marks/kin.png' },
];
const error = ref('');
const googleErrors = {
    failed: 'Login Google gagal. Coba lagi.',
    unregistered: 'Email Google ini belum terdaftar. Hubungi admin.',
    domain: 'Gunakan email perusahaan yang sudah terdaftar.',
    mismatch: 'Akun Google ini terhubung ke karyawan lain.',
};
const googleHref = computed(() => {
    const target = route.query.redirect;

    return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
        ? `/auth/google?redirect=${encodeURIComponent(target)}`
        : '/auth/google';
});
const submitting = ref(false);
const stageRef = ref(null);
const sheetHeight = ref(null);
const dragging = ref(false);
const sheetStyle = computed(() => (sheetHeight.value == null ? {} : { '--sheet-h': `${sheetHeight.value}px` }));
let dragOrigin = 0;
let heightOrigin = 0;
let dragMin = 0;
let dragMax = 0;

function sheetBounds() {
    const stage = stageRef.value;
    const screen = stage?.parentElement?.clientHeight || window.innerHeight;
    const content = stage?.querySelector('.login-sheet-body')?.offsetHeight || 0;

    return {
        min: Math.min(content, screen),
        max: screen,
    };
}

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

    const bounds = sheetBounds();
    dragging.value = true;
    dragOrigin = event.clientY;
    heightOrigin = sheetHeight.value ?? stageRef.value?.offsetHeight ?? bounds.min;
    dragMin = bounds.min;
    dragMax = bounds.max;
    window.addEventListener('pointermove', moveDrag);
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);
}

function moveDrag(event) {
    if (!dragging.value) {
        return;
    }

    const next = heightOrigin + dragOrigin - event.clientY;
    sheetHeight.value = Math.round(Math.min(dragMax, Math.max(dragMin, next)));
}

function endDrag() {
    dragging.value = false;
    window.removeEventListener('pointermove', moveDrag);
    window.removeEventListener('pointerup', endDrag);
    window.removeEventListener('pointercancel', endDrag);

    if (sheetHeight.value == null) {
        return;
    }

    const { min } = sheetBounds();

    if (sheetHeight.value <= min + 8) {
        sheetHeight.value = null;
    }
}

function fitSheet() {
    if (sheetHeight.value == null || !window.matchMedia('(max-width: 900px)').matches) {
        return;
    }

    const { min, max } = sheetBounds();
    sheetHeight.value = Math.min(max, Math.max(min, sheetHeight.value));
}

onMounted(() => {
    window.addEventListener('resize', fitSheet);

    if (typeof route.query.google === 'string') {
        error.value = googleErrors[route.query.google] || '';
    }
});
onBeforeUnmount(() => {
    endDrag();
    window.removeEventListener('resize', fitSheet);
});
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
        const target = route.query.redirect;
        const next = typeof target === 'string' && target.startsWith('/') && !target.startsWith('//')
            ? target
            : homePath();
        await router.push(next);
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
                    <BrandMark />
                </div>
                <div>
                    <div class="brandmark-name">Product Claim</div>
                </div>
            </div>
            <div class="login-hero-copy">
                <p class="login-greeting">{{ greeting }}</p>
                <h1>Katalog klaim<br><span>khusus untuk kamu.</span></h1>
                <p>Pilih produk, ajukan klaim, dan pantau progres dalam satu tempat.</p>
                <div class="login-pills">
                    <span v-for="brand in brandMarks" :key="brand.name">
                        <img :src="brand.src" :alt="brand.name">
                    </span>
                </div>
            </div>
            <p class="login-footer">Product Claim untuk karyawan</p>
        </section>
        <div
            ref="stageRef"
            class="login-stage"
            :class="{ dragging }"
            :style="sheetStyle"
            @pointerdown="startDrag"
        >
            <div class="login-sheet-body">
            <div class="login-sheet-handle" aria-hidden="true"></div>
            <form class="login-card" @submit.prevent="submit">
                <h1>Selamat datang</h1>
                <p>Masuk untuk melanjutkan ke Product Claim.</p>
                <label class="login-field">
                    <div class="login-control">
                        <input v-model="email" type="email" autocomplete="username" required placeholder="nama@perusahaan.com" aria-label="Email">
                    </div>
                </label>
                <label class="login-field">
                    <div class="login-control">
                        <input v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required placeholder="Masukkan password" aria-label="Password">
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
                <div v-if="error" class="login-alert" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="12" cy="16.2" r="0.8" fill="currentColor"/></svg>
                    <span>{{ error }}</span>
                </div>
                <button class="login-submit" type="submit" :disabled="submitting">{{ submitting ? 'Memeriksa...' : 'Masuk' }}</button>
                <div class="login-divider">atau</div>
                <a class="google-btn" :href="googleHref">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.7Z"/><path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.4 14.4A7.2 7.2 0 0 1 5 12c0-.8.1-1.6.4-2.4V6.5H1.4A12 12 0 0 0 0 12c0 1.9.5 3.8 1.4 5.5l4-3.1Z"/><path fill="#EA4335" d="M12 4.8c1.7 0 3.3.6 4.5 1.8l3.4-3.4A12 12 0 0 0 1.4 6.5l4 3.1C6.3 6.8 8.9 4.8 12 4.8Z"/></svg>
                    Masuk dengan Google
                </a>
            </form>
            <p class="login-footer login-footer-sheet">Product Claim untuk karyawan</p>
            </div>
        </div>
    </div>
</template>
