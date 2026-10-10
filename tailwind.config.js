import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class', '[data-theme="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                display: ['Fraunces', 'Georgia', 'serif'],
            },
            colors: {
                brand: {
                    DEFAULT: '#1f4d43',
                    light: '#2a6558',
                    soft: '#e2efe9',
                },
                gold: {
                    DEFAULT: '#e0a526',
                    soft: '#fbf0d0',
                },
                side: '#143a32',
            },
        },
    },
    plugins: [forms],
};
