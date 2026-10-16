<script setup>
import AppHeader from '../components/AppHeader.vue';
import ClaimBoard from '../components/ClaimBoard.vue';
</script>

<template>
    <div class="app-shell">
        <AppHeader />
        <main class="admin-main">
            <div class="admin-heading">
                <div>
                    <h1>Progres pesanan</h1>
                    <p>Satu invoice per brand: cek stok, transfer ke PT brand, verifikasi Finance, lalu gudang menyiapkan barang.</p>
                </div>
            </div>
            <ClaimBoard />
        </main>
    </div>
</template>
