import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Cormorant Garamond', 'Georgia', 'serif'],
                ui: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                bollywood: {
                    cream: '#fef7ed',
                    sand: '#fff7ed',
                    peach: '#ffedd5',
                    gold: '#b45309',
                    'gold-light': '#f59e0b',
                    rose: '#fdf2f8',
                    maroon: '#9f1239',
                },
            },
            animation: {
                'sidebar-in': 'sidebarIn 0.3s ease-out forwards',
                'sidebar-out': 'sidebarOut 0.25s ease-in forwards',
                'fade-in': 'fadeIn 0.4s ease-out forwards',
                'slide-up': 'slideUp 0.4s ease-out forwards',
            },
            keyframes: {
                sidebarIn: {
                    '0%': { transform: 'translateX(-100%)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
                sidebarOut: {
                    '0%': { transform: 'translateX(0)', opacity: '1' },
                    '100%': { transform: 'translateX(-100%)', opacity: '0.8' },
                },
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            backdropBlur: {
                xs: '2px',
            },
        },
    },

    plugins: [forms],
};
