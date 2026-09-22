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
                '2xs': ['13px', { lineHeight: '18px' }],
                xs:    ['14px', { lineHeight: '20px' }],
                sm:    ['15px', { lineHeight: '22px' }],
                base:  ['17px', { lineHeight: '26px' }],
                md:    ['19px', { lineHeight: '28px' }],
                xl:    ['24px', { lineHeight: '32px' }],
                '2xl': ['30px', { lineHeight: '38px' }],
                '3xl': ['40px', { lineHeight: '48px' }],
            },

            maxWidth: {
                estudiante: '640px',
                'estudiante-ancho': '960px',
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
