import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            // Diambil langsung dari desain Figma Gapoktan.
            colors: {
                brand: {
                    50: '#ECFCF5',
                    100: '#D1FAE5',
                    200: '#A7F3D0',
                    300: '#6EE7B7',
                    400: '#34D399',
                    500: '#059669',
                    600: '#047857',
                    700: '#006C49',
                    800: '#065F46',
                    900: '#064E3B',
                },
                danger: {
                    50: '#FEF2F2',
                    100: '#FEE2E2',
                    500: '#F94141',
                    600: '#DC2626',
                    700: '#B91C1C',
                },
                warn: {
                    50: '#FFFBEB',
                    100: '#FEF3C7',
                    600: '#D97706',
                    700: '#B45309',
                },
            },

            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04)',
                pop: '0 10px 30px -12px rgb(15 23 42 / 0.18)',
            },

            spacing: {
                sidebar: '16.25rem', // 260px, lebar sidebar di desain
            },
        },
    },

    plugins: [forms],
};
