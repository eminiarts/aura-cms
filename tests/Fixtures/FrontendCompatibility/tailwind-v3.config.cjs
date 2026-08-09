function withOpacityValue(variable) {
    return ({ opacityValue }) => {
        if (opacityValue === undefined) {
            return `rgb(var(${variable}))`;
        }

        return `rgb(var(${variable}) / ${opacityValue})`;
    };
}

module.exports = {
    content: {
        relative: true,
        files: [
            './aura-source/**/*.blade.php',
            './aura-source/**/*.php',
        ],
    },
    darkMode: 'selector',
    theme: {
        extend: {
            colors: {
                aura: {
                    primary: withOpacityValue('--aura-color-primary'),
                    background: withOpacityValue('--aura-color-background'),
                    panel: withOpacityValue('--aura-color-panel'),
                    border: withOpacityValue('--aura-color-border'),
                    text: withOpacityValue('--aura-color-text'),
                    muted: withOpacityValue('--aura-color-muted'),
                    success: withOpacityValue('--aura-color-success'),
                    warning: withOpacityValue('--aura-color-warning'),
                    danger: withOpacityValue('--aura-color-danger'),
                },
                primary: {
                    400: withOpacityValue('--primary-400'),
                    500: withOpacityValue('--primary-500'),
                    600: withOpacityValue('--primary-600'),
                    700: withOpacityValue('--primary-700'),
                },
                gray: {
                    100: withOpacityValue('--gray-100'),
                    800: withOpacityValue('--gray-800'),
                    900: withOpacityValue('--gray-900'),
                    950: withOpacityValue('--gray-950'),
                },
                sidebar: {
                    bg: withOpacityValue('--sidebar-bg'),
                    text: withOpacityValue('--sidebar-text'),
                },
            },
            fontFamily: {
                sans: [
                    'var(--aura-font-sans)',
                    'ui-sans-serif',
                    'system-ui',
                    'sans-serif',
                    '"Apple Color Emoji"',
                    '"Segoe UI Emoji"',
                    '"Segoe UI Symbol"',
                    '"Noto Color Emoji"',
                ],
            },
        },
    },
};
