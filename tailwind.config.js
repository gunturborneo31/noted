import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Space Grotesk', 'Space Mono', ...defaultTheme.fontFamily.sans],
                mono: ['Space Mono', 'Courier New', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                lime: {
                    50:  '#f7ffe5',
                    100: '#ecfccb',
                    200: '#d9f99d',
                    300: '#bef264',
                    400: '#a3e635',
                    500: '#84cc16',
                    600: '#65a30d',
                    700: '#4d7c0f',
                    800: '#3f6212',
                    900: '#365314',
                    950: '#1a2e05',
                },
            },
            boxShadow: {
                'neo':    '4px 4px 0px 0px rgba(0,0,0,1)',
                'neo-sm': '2px 2px 0px 0px rgba(0,0,0,1)',
                'neo-lg': '6px 6px 0px 0px rgba(0,0,0,1)',
                'neo-xl': '8px 8px 0px 0px rgba(0,0,0,1)',
            },
        },
    },
    plugins: [],
};
