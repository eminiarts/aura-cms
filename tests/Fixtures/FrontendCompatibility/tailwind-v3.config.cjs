const defaultTheme = require('tailwindcss/defaultTheme');

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
        files: ['./representative.html'],
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
                    500: withOpacityValue('--primary-500'),
                    600: withOpacityValue('--primary-600'),
                },
                sidebar: {
                    bg: withOpacityValue('--sidebar-bg'),
                    text: withOpacityValue('--sidebar-text'),
                },
            },
            fontFamily: {
                sans: ['var(--aura-font-sans)', ...defaultTheme.fontFamily.sans],
            },
        },
    },
};
