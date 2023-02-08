const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */

function withOpacityValue(variable) {
  return ({ opacityValue }) => {
    if (opacityValue === undefined) {
      return `rgb(var(${variable}))`
    }
    return `rgb(var(${variable}) / ${opacityValue})`
  }
}

module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            backgroundImage: {
                'gradient-conic': 'conic-gradient(var(--tw-gradient-stops))',
            },
            keyframes: {
                disco: {
                '0%': { transform: 'translateY(-50%) rotate(0deg)' },
                '100%': { transform: 'translateY(-50%) rotate(360deg)' },
                },
            },
            animation: {
                disco: 'disco 1.5s linear infinite',
            },
            colors: {
                primary: {
                    '25': withOpacityValue('--primary-25'),
                    '50':  withOpacityValue('--primary-50'),
                    '100': withOpacityValue('--primary-100'),
                    '200': withOpacityValue('--primary-200'),
                    '300': withOpacityValue('--primary-300'),
                    '400': withOpacityValue('--primary-400'),
                    '500': withOpacityValue('--primary-500'),
                    '600': withOpacityValue('--primary-600'),
                    '700': withOpacityValue('--primary-700'),
                    '800': withOpacityValue('--primary-800'),
                    '900': withOpacityValue('--primary-900'),
                },

                gray: {
                    '25': withOpacityValue('--gray-25'),
                    '50':  withOpacityValue('--gray-50'),
                    '100': withOpacityValue('--gray-100'),
                    '200': withOpacityValue('--gray-200'),
                    '300': withOpacityValue('--gray-300'),
                    '400': withOpacityValue('--gray-400'),
                    '500': withOpacityValue('--gray-500'),
                    '600': withOpacityValue('--gray-600'),
                    '700': withOpacityValue('--gray-700'),
                    '800': withOpacityValue('--gray-800'),
                    '900': withOpacityValue('--gray-900'),
                }
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'xs': '0px 1px 2px rgba(0, 8, 24, 0.05)',
            },
            spacing: {
                '4.5': '1.125rem',
            }
        },
    },

    purge: {
        enable: true,
        options: {
            safelist: [
                'w-full',
                'sm:max-w-2xl',
                'sm:max-w-3xl',
                'sm:max-w-4xl',
                'sm:max-w-5xl',
                'sm:max-w-6xl',
                'sm:max-w-7xl',
                'md:max-w-xl',
                'lg:max-w-3xl',
                'xl:max-w-5xl',
                'sm:max-w-md',
                '2xl:max-w-7xl',
                'xl:max-w-7xl',
                'max-w-7xl',
                'w-1/2',
                /w$/ ],
        },
        content: [
            './storage/framework/views/*.php',
            './app/**/*.php',
            './vendor/wire-elements/modal/resources/views/*.blade.php',
            './resources/views/**/*.blade.php'
        ]
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography'), require('tailwind-scrollbar'), require('@tailwindcss/aspect-ratio'),],
};
