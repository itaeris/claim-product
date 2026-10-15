<script setup>
defineProps({
    discount: {
        type: Number,
        default: 30,
    },
});
</script>

<template>
    <section class="hero">
        <div class="hero-copy">
            <div class="eyebrow">Employee benefit</div>
            <h1>Pilih produk favoritmu,<br>khusus untuk kamu.</h1>
            <p>Nikmati harga spesial karyawan hingga {{ discount }}% setiap periode klaim.</p>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-bottle"></div>
            <div class="hero-bag"></div>
        </div>
    </section>
</template>
