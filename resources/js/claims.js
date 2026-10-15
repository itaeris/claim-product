export const claimSteps = [
    { id: 'diajukan', label: 'Diajukan' },
    { id: 'diproses', label: 'Diproses' },
    { id: 'disetujui', label: 'Disetujui' },
    { id: 'selesai', label: 'Selesai' },
];

export const goodsSteps = [
    { id: 'diajukan', label: 'Belum dibayar' },
    { id: 'diproses', label: 'Proses' },
    { id: 'disetujui', label: 'Dikirim' },
    { id: 'selesai', label: 'Selesai' },
];

export const claimStatuses = [
    ...claimSteps,
    { id: 'ditolak', label: 'Ditolak' },
];

export function statusLabel(status) {
    return claimStatuses.find((item) => item.id === status)?.label ?? status;
}

export function stepState(status, index, steps = claimSteps) {
    if (status === 'ditolak') {
        return 'rejected';
    }

    const current = steps.findIndex((step) => step.id === status);

    if (index < current) {
        return 'done';
    }

    if (index === current) {
        return 'current';
    }

    return '';
}
