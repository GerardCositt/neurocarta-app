const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');

module.exports = {
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            /* Tailwind v2 no soporta text-[7px] (arbitrary values); tamaños extra para UI compacta (p. ej. banner saldo IA). */
            fontSize: {
                '2xs': ['0.625rem', { lineHeight: '0.875rem' }], // 10px @16px root
                '3xs': ['0.5625rem', { lineHeight: '0.8125rem' }], // 9px
                '4xs': ['0.4375rem', { lineHeight: '0.625rem' }], // 7px
            },
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            /* Por defecto Tailwind v2 solo expone `yellow` (mapea a la paleta amber), no `amber` ni `sky`/`orange`. Sin esto, clases como bg-amber-50 no generan CSS y el panel parece gris. */
            colors: {
                amber:  colors.amber,
                sky:    colors.sky,
                orange: colors.orange,
            },
        },
    },

    variants: {
        extend: {
            opacity: ['disabled'],
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
