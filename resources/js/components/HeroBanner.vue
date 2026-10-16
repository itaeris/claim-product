<script setup>
defineProps({
    discount: {
        type: Number,
        default: 40,
    },
    skuLimit: {
        type: Number,
        default: 5,
    },
});
</script>

<template>
    <section class="hero">
        <div class="hero-copy">
            <div class="eyebrow">Employee purchase</div>
            <h1>Pilih produk favoritmu,<br>khusus untuk kamu.</h1>
            <p>Diskon karyawan hingga {{ discount }}% sesuai brand. Maks. {{ skuLimit }} pcs per SKU per tahun.</p>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-bottle"></div>
            <div class="hero-bag"></div>
        </div>
    </section>
</template>
