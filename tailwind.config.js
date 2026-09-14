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
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                ink: '#16202e',
                navy: {
                    DEFAULT: '#1b3a5c',
                    light: '#2a5c8f',
                    lighter: '#6a8ba8',
                },
                slate: {
                    50: '#f6f7f9',
                    100: '#f2f4f6',
                    200: '#eef1f4',
                    300: '#e3e7ec',
                    400: '#dde2e8',
                    500: '#cfd7e0',
                    600: '#b3c1d1',
                    700: '#8a95a3',
                    800: '#7a8696',
                    900: '#5b6878',
                },
                danger: {
                    bg: '#fbeceb',
                    border: '#f1d3d1',
                    ink: '#b3261e',
                    deep: '#96201a',
                },
                warn: {
                    bg: '#fdf3e3',
                    border: '#f0dcbd',
                    ink: '#a6620a',
                    deep: '#8a5208',
                },
                ok: {
                    bg: '#eaf2ee',
                    border: '#d5e6de',
                    ink: '#17795e',
                    deep: '#146050',
                },
                info: {
                    bg: '#eef3f8',
                    border: '#d6e0ea',
                    ink: '#1b3a5c',
                },
            },
        },
    },

    plugins: [forms],
};
