const stepOf = {
    cek_stok: 0,
    stok_kosong: 0,
    menunggu_pembayaran: 1,
    kurang_bayar: 1,
    verifikasi_pembayaran: 2,
    disiapkan: 3,
    dikirim: 4,
    siap_diambil: 4,
    selesai: 5,
};

const warnings = ['stok_kosong', 'kurang_bayar'];

export const awaitingPayment = ['menunggu_pembayaran', 'kurang_bayar'];

export function claimSteps(claim) {
    return [
        { id: 'cek_stok', label: 'Cek stok' },
        { id: 'pembayaran', label: 'Pembayaran' },
        { id: 'verifikasi', label: 'Verifikasi' },
        { id: 'disiapkan', label: 'Disiapkan' },
        { id: 'diserahkan', label: claim?.fulfillment === 'delivery' ? 'Dikirim' : 'Siap diambil' },
        { id: 'selesai', label: 'Selesai' },
    ];
}

export function stepState(claim, index) {
    if (claim.status === 'dibatalkan') {
        return 'rejected';
    }

    const current = stepOf[claim.status] ?? 0;

    if (claim.status === 'selesai' || index < current) {
        return 'done';
    }

    if (index === current) {
        return warnings.includes(claim.status) ? 'current warn' : 'current';
    }

    return '';
}

export function statusTone(status) {
    if (status === 'dibatalkan') {
        return 'off';
    }

    return warnings.includes(status) ? 'warn' : '';
}

export function isFinished(claim) {
    return claim.status === 'selesai' || claim.status === 'dibatalkan';
}
