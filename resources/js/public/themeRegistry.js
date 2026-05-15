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
};

export function resolveTheme(themeKey) {
    return themes[themeKey] || themes.default;
}

export default themes;
