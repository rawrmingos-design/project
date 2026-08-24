const themes = {
    default: {
        name: 'Default',
        tokens: {
            radius: '24px',
            heroAlign: 'left',
            cardShadow: '0 20px 40px rgba(15, 23, 42, 0.22)',
            shellMaxWidth: '1200px',
        },
    },
    bangjeff: {
        name: 'Bangjeff',
        tokens: {
            radius: '16px',
            heroAlign: 'left',
            cardShadow: '0 18px 42px rgba(0, 0, 0, 0.28)',
            shellMaxWidth: '1280px',
            colors: {
                primary: '#1a1a1d',
                secondary: '#242427',
                accent: '#f97316',
                highlight: '#fb923c',
            },
        },
    },
    istanatopup: {
        name: 'IstanaTopup',
        tokens: {
            radius: '16px',
            heroAlign: 'left',
            cardShadow: '0 12px 32px rgba(0, 0, 0, 0.35)',
            shellMaxWidth: '1200px',
            colors: {
                primary: '#3D79F2',
                secondary: '#1A1A1A',
                accent: '#F97316',
                highlight: '#FB923C',
            },
            background: '#121212',
            surface: '#1A1A1A',
            surfaceAlt: '#1F1F1F',
            border: '#262626',
            textMuted: '#9C9C9C',
            success: '#2BB673',
            danger: '#E5484D',
            font: "'Plus Jakarta Sans', system-ui, sans-serif",
        },
    },
};

export function resolveTheme(themeKey) {
    return themes[themeKey] || themes.default;
}

export default themes;
