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
                // Comillas internas obligatorias: sin ellas el minificador emite
                // `font-family:Source Sans 3,…`, que es CSS inválido (el "3" no es
                // un identificador) y el navegador descarta toda la declaración.
                sans:    ['"Source Sans 3"', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', ...defaultTheme.fontFamily.sans],
                mono:    ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                gg: {
                    papel:            'rgb(var(--gg-papel-rgb) / <alpha-value>)',
                    superficie:       'rgb(var(--gg-superficie-rgb) / <alpha-value>)',
                    tinta:            'rgb(var(--gg-tinta-rgb) / <alpha-value>)',
                    'tinta-suave':    'rgb(var(--gg-tinta-suave-rgb) / <alpha-value>)',
                    borde:            'rgb(var(--gg-borde-rgb) / <alpha-value>)',
                    primario:         'rgb(var(--gg-primario-rgb) / <alpha-value>)',
                    'primario-suave': 'rgb(var(--gg-primario-suave-rgb) / <alpha-value>)',
                    'primario-hondo': 'rgb(var(--gg-primario-hondo-rgb) / <alpha-value>)',
                    'primario-noche': 'rgb(var(--gg-primario-noche-rgb) / <alpha-value>)',
                    acento:           'rgb(var(--gg-acento-rgb) / <alpha-value>)',
                    'papel-hondo':    'rgb(var(--gg-papel-hondo-rgb) / <alpha-value>)',
                    'riesgo-bajo':    'rgb(var(--gg-riesgo-bajo-rgb) / <alpha-value>)',
                    'riesgo-medio':   'rgb(var(--gg-riesgo-medio-rgb) / <alpha-value>)',
                    'riesgo-alto':    'rgb(var(--gg-riesgo-alto-rgb) / <alpha-value>)',
                },
            },

            borderRadius: {
                control: '10px',
                tarjeta: '16px',
                bloque:  '24px',
            },

            // Elevación: sombras suaves teñidas de la tinta verde, nunca negras.
            boxShadow: {
                'elev-1': '0 1px 2px rgba(22, 48, 43, 0.04), 0 1px 3px rgba(22, 48, 43, 0.06)',
                'elev-2': '0 2px 4px rgba(22, 48, 43, 0.04), 0 8px 24px -6px rgba(22, 48, 43, 0.12)',
                'elev-3': '0 4px 8px rgba(22, 48, 43, 0.05), 0 24px 48px -12px rgba(22, 48, 43, 0.22)',
                'boton':  '0 1px 0 rgba(255, 255, 255, 0.12) inset, 0 1px 2px rgba(19, 61, 48, 0.25), 0 4px 12px -4px rgba(19, 61, 48, 0.35)',
            },

            keyframes: {
                'gg-entrada': {
                    from: { opacity: '0', transform: 'translateY(8px)' },
                    to:   { opacity: '1', transform: 'translateY(0)' },
                },
                'gg-giro': {
                    to: { transform: 'rotate(360deg)' },
                },
            },
            animation: {
                'gg-entrada': 'gg-entrada 420ms cubic-bezier(0.22, 1, 0.36, 1) both',
                'gg-giro':    'gg-giro 700ms linear infinite',
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
                '3xl': ['36px', { lineHeight: '42px', letterSpacing: '-0.01em' }],
                '4xl': ['46px', { lineHeight: '50px', letterSpacing: '-0.02em' }],
            },

            maxWidth: {
                estudiante: '640px',
                'estudiante-amplio': '1040px',
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
