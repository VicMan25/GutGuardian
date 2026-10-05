import './bootstrap';
import Alpine from 'alpinejs';
import { Chart, LineController, LineElement, PointElement, BarController, BarElement, LinearScale, CategoryScale, Legend, Tooltip } from 'chart.js';

// Barras: reporte institucional (HU-022). Líneas: seguimiento y ficha (HU-008/HU-010).
Chart.register(LineController, LineElement, PointElement, BarController, BarElement, LinearScale, CategoryScale, Legend, Tooltip);
Chart.defaults.font.family = "'Source Sans 3', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#55615B';
Chart.defaults.borderColor = '#E9ECE6';
Chart.defaults.elements.line.borderWidth = 2.5;
Chart.defaults.elements.point.radius = 3.5;
Chart.defaults.elements.point.hoverRadius = 6;
Chart.defaults.elements.point.borderWidth = 2;
Chart.defaults.elements.point.backgroundColor = '#FFFFFF';
Chart.defaults.elements.bar.borderRadius = 8;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.tooltip.backgroundColor = '#0F2E25';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.titleFont = { weight: '500' };
Chart.defaults.plugins.tooltip.boxPadding = 4;

window.Alpine = Alpine;
window.Chart = Chart;

// ================================================================
// escalaFrecuencia — selector segmentado de 4 niveles
// Nunca=0, Ocasionalmente=1, Algunas veces=2, Siempre=3
// Usado en P01-P07, P09, P10, P12, P17, P19
// ================================================================
Alpine.data('escalaFrecuencia', (valorInicial = null) => ({
    seleccionado: valorInicial,
    enfocado: null,

    siguiente() {
        if (this.seleccionado === null) { this.seleccionado = 0; }
        else if (this.seleccionado < 3) { this.seleccionado++; }
        this.moverFoco(this.seleccionado);
    },

    anterior() {
        if (this.seleccionado === null) { this.seleccionado = 3; }
        else if (this.seleccionado > 0) { this.seleccionado--; }
        this.moverFoco(this.seleccionado);
    },

    seleccionar(valor) {
        this.seleccionado = valor;
        this.enfocado = valor;
    },

    moverFoco(valor) {
        this.$nextTick(() => {
            const el = this.$el.querySelector(`input[value="${valor}"]`);
            if (el) el.focus();
        });
    },
}));

// ================================================================
// escalaDolor — selector 1-5 para P13
// ================================================================
Alpine.data('escalaDolor', (valorInicial = null) => ({
    seleccionado: valorInicial,
    enfocado: null,

    siguiente() {
        if (this.seleccionado === null) { this.seleccionado = 1; }
        else if (this.seleccionado < 5) { this.seleccionado++; }
        this.moverFoco(this.seleccionado);
    },

    anterior() {
        if (this.seleccionado === null) { this.seleccionado = 5; }
        else if (this.seleccionado > 1) { this.seleccionado--; }
        this.moverFoco(this.seleccionado);
    },

    seleccionar(valor) {
        this.seleccionado = valor;
        this.enfocado = valor;
    },

    moverFoco(valor) {
        this.$nextTick(() => {
            const el = this.$el.querySelector(`input[value="${valor}"]`);
            if (el) el.focus();
        });
    },
}));

// ================================================================
// opcionMultiple — checkboxes con "Ninguna" excluyente
// Usado en P14, P15, P18, P20
// idNinguna: el ID de base de datos de la opción "Ninguna"
// ================================================================
Alpine.data('opcionMultiple', (seleccionadosInicial = [], idNinguna = null) => ({
    seleccionados: [...seleccionadosInicial],
    idNinguna,

    toggle(id) {
        const esNinguna = (id === this.idNinguna);

        if (esNinguna) {
            // "Ninguna" actúa como toggle exclusivo
            this.seleccionados = this.marcado(id) ? [] : [id];
            return;
        }

        // Al elegir cualquier otra opción, deseleccionar "Ninguna"
        this.seleccionados = this.seleccionados.filter(v => v !== this.idNinguna);

        const idx = this.seleccionados.indexOf(id);
        if (idx === -1) {
            this.seleccionados.push(id);
        } else {
            this.seleccionados.splice(idx, 1);
        }
    },

    marcado(id) {
        return this.seleccionados.includes(id);
    },
}));

// ================================================================
// matrizSintomas — acordeón en móvil, tabla en desktop
// Usado en P11, P12, P16, P17
// ================================================================
Alpine.data('matrizSintomas', (totalItems = 0, respuestasIniciales = {}) => ({
    expandido: Object.keys(respuestasIniciales).length === totalItems ? null : 0,
    respuestas: { ...respuestasIniciales },
    totalItems,

    expandir(idx) {
        this.expandido = (this.expandido === idx) ? null : idx;
    },

    respondido(idx) {
        return this.respuestas[idx] !== undefined;
    },

    responder(itemIdx, valor) {
        this.respuestas[itemIdx] = valor;
        // Avanzar al siguiente ítem automáticamente
        const siguiente = itemIdx + 1;
        if (siguiente < this.totalItems) {
            setTimeout(() => { this.expandido = siguiente; }, 220);
        } else {
            setTimeout(() => { this.expandido = null; }, 220);
        }
    },

    totalRespondidos() {
        return Object.keys(this.respuestas).length;
    },

    todosRespondidos() {
        return this.totalRespondidos() === this.totalItems;
    },
}));

// ================================================================
// selectorUnico — radios verticales para preguntas 'single' con
// opciones propias (no la escala de 4 niveles): SD1-SD4, P08.
// ================================================================
Alpine.data('selectorUnico', (seleccionadoInicial = null) => ({
    seleccionado: seleccionadoInicial,

    marcado(id) {
        return this.seleccionado === id;
    },

    seleccionar(id) {
        this.seleccionado = id;
    },
}));

// ================================================================
// Estado de carga al enviar formularios POST: el botón que envía
// muestra un indicador y queda inactivo hasta la respuesta. Evita el
// doble envío (p. ej. de una sección de la encuesta). No aplica a GET
// (búsquedas y filtros) ni a formularios marcados con data-sin-carga.
// ================================================================
document.addEventListener('submit', (evento) => {
    const formulario = evento.target;
    if (evento.defaultPrevented || formulario.method?.toLowerCase() !== 'post' || 'sinCarga' in formulario.dataset) {
        return;
    }

    const boton = evento.submitter
        ?? formulario.querySelector('button[type="submit"], button:not([type])');
    if (!boton) return;

    // Se marca en el siguiente ciclo para no alterar el valor enviado por el botón.
    requestAnimationFrame(() => {
        boton.setAttribute('data-cargando', '');
        boton.setAttribute('aria-busy', 'true');
    });
});

// Al volver con "Atrás" el navegador puede restaurar la página desde caché
// con el botón todavía en estado de carga.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-cargando]').forEach((el) => {
        el.removeAttribute('data-cargando');
        el.removeAttribute('aria-busy');
    });
});

Alpine.start();
