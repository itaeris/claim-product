const logos = {
    AB: '/logo-brand/marks/aeris.png',
    FTI: '/logo-brand/marks/fti.png',
    'K+A': '/logo-brand/marks/kin.png',
};

export function brandLogo(brand) {
    return logos[brand?.short] ?? null;
}
