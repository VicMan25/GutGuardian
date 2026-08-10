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
                sans:    ['Source Sans 3', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', ...defaultTheme.fontFamily.sans],
                mono:    ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                gg: {
                    papel:            'var(--gg-papel)',
                    superficie:       'var(--gg-superficie)',
                    tinta:            'var(--gg-tinta)',
                    'tinta-suave':    'var(--gg-tinta-suave)',
                    borde:            'var(--gg-borde)',
                    primario:         'var(--gg-primario)',
                    'primario-suave': 'var(--gg-primario-suave)',
                    'riesgo-bajo':    'var(--gg-riesgo-bajo)',
                    'riesgo-medio':   'var(--gg-riesgo-medio)',
                    'riesgo-alto':    'var(--gg-riesgo-alto)',
                },
            },

            borderRadius: {
                control: '8px',
                tarjeta: '12px',
            },

            // Escala tipográfica del sistema de diseño (solo estos tamaños permitidos)
            fontSize: {
                '2xs': ['12px', { lineHeight: '16px' }],
                xs:    ['13px', { lineHeight: '18px' }],
                sm:    ['14px', { lineHeight: '20px' }],
                base:  ['16px', { lineHeight: '24px' }],
                md:    ['17px', { lineHeight: '26px' }],
                xl:    ['22px', { lineHeight: '30px' }],
                '2xl': ['28px', { lineHeight: '36px' }],
                '3xl': ['36px', { lineHeight: '44px' }],
            },

            maxWidth: {
                estudiante: '640px',
            },

            spacing: {
                18: '4.5rem',
            },
        },
    },

    plugins: [
        forms,
    ],
};
