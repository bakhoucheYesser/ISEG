import { defineConfig } from '@tailwindcss/postcss';

export default defineConfig({
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
        "./src/**/*.php"
    ],
    theme: {
        extend: {
            colors: {
                'iseg-navy': {
                    50: '#f0f4ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                },
                'iseg-gold': {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                }
            }
        },
    },
    plugins: [],
});
