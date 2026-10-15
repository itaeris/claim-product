const rupiahFormat = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

export function rupiah(value) {
    return rupiahFormat.format(value || 0);
}
