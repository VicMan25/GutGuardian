import './bootstrap';
import Alpine from 'alpinejs';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip);
Chart.defaults.font.family = "'Source Sans 3', system-ui, sans-serif";

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

Alpine.start();
