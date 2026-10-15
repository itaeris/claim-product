export const claimSteps = [
    { id: 'diajukan', label: 'Diajukan' },
    { id: 'diproses', label: 'Diproses' },
    { id: 'disetujui', label: 'Disetujui' },
    { id: 'selesai', label: 'Selesai' },
];

export const claimStatuses = [
    ...claimSteps,
    { id: 'ditolak', label: 'Ditolak' },
];

export function statusLabel(status) {
    return claimStatuses.find((item) => item.id === status)?.label ?? status;
}

export function stepState(status, index) {
    if (status === 'ditolak') {
        return 'rejected';
    }

    const current = claimSteps.findIndex((step) => step.id === status);

    if (index < current) {
        return 'done';
    }

    if (index === current) {
        return 'current';
    }

    return '';
}
