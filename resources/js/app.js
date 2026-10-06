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
// Origen de la última interacción. El avance automático (de ítem o de
// pregunta) solo ocurre tras un toque o clic: con el teclado, las
// flechas cambian la selección dentro de un radiogroup y avanzar en ese
// momento sacaría al usuario del control que está recorriendo.
// ================================================================
let ultimoPuntero = 0;
document.addEventListener('pointerdown', () => { ultimoPuntero = performance.now(); }, true);
const fuePuntero = () => performance.now() - ultimoPuntero < 1000;

const sinMovimiento = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ================================================================
// matrizSintomas — un ítem (síntoma / medicamento) a la vez, con chips
// para ver el avance y saltar entre ítems, y un resumen al completar.
// Usado en P09, P11, P12, P16, P17
// ================================================================
Alpine.data('matrizSintomas', (totalItems = 0, respuestasIniciales = {}) => ({
    expandido: null,
    respuestas: { ...respuestasIniciales },
    totalItems,
    temporizador: null,

    init() {
        // Al reanudar se abre el primer ítem sin responder; si todos lo
        // están, se muestra el resumen.
        this.expandido = this.siguientePendiente(-1);
    },

    expandir(idx) {
        this.expandido = (this.expandido === idx) ? null : idx;
    },

    respondido(idx) {
        return this.respuestas[idx] !== undefined;
    },

    // Primer ítem sin responder después de `desde`; si no hay, busca desde
    // el inicio. null cuando todos están respondidos.
    siguientePendiente(desde) {
        for (let i = desde + 1; i < this.totalItems; i++) {
            if (!this.respondido(i)) return i;
        }
        for (let i = 0; i <= desde && i < this.totalItems; i++) {
            if (!this.respondido(i)) return i;
        }
        return null;
    },

    responder(itemIdx, valor) {
        this.respuestas[itemIdx] = valor;
        if (!fuePuntero()) return;

        clearTimeout(this.temporizador);
        this.temporizador = setTimeout(() => {
            if (this.expandido === itemIdx) {
                this.expandido = this.siguientePendiente(itemIdx);
            }
        }, 260);
    },

    // Botón explícito "Siguiente": para teclado y para quien corrige un ítem.
    // Con todo respondido vuelve al resumen.
    avanzarItem(itemIdx) {
        this.expandido = this.siguientePendiente(itemIdx);
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
// encuestaGuiada — presenta una sección de la encuesta como una
// secuencia de pasos (una pregunta por paso). Es solo presentación:
// todas las preguntas siguen dentro del mismo <form>, los pasos ocultos
// también se envían, y el servidor valida y guarda la sección completa
// igual que antes (EncuestaController::guardar).
//
// config.total   número de pasos (preguntas de la sección)
// config.inicial paso que abre el servidor: primera pregunta con error
//                de validación o, si no hay, la primera sin responder
// ================================================================
Alpine.data('encuestaGuiada', ({ total, inicial = 0 }) => ({
    total,
    paso: inicial,
    direccion: 1,
    respondidas: [],
    aviso: null,
    sacudir: false,
    sucio: false,
    enviando: false,
    temporizador: null,
    raiz: null,

    init() {
        // En Alpine 3, this.$el dentro de un método es el elemento que disparó
        // el evento (p. ej. el botón "Siguiente"), no el <form>: se guarda la raíz.
        this.raiz = this.$el;
        // Los controles hijos fijan su :checked al inicializarse; se revisa
        // el estado después de que todos lo hayan hecho.
        this.$nextTick(() => this.revisar());
    },

    pasos() {
        return [...this.raiz.querySelectorAll('[data-paso]')];
    },

    // Un paso está respondido cuando cada grupo de inputs (cada `name`)
    // tiene al menos una opción marcada: sirve para escala, single,
    // multiple (un grupo de checkboxes) y matriz (un grupo por ítem).
    pasoRespondido(el) {
        const grupos = {};
        el.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((input) => {
            (grupos[input.name] ??= []).push(input);
        });
        const nombres = Object.keys(grupos);
        return nombres.length > 0 && nombres.every((n) => grupos[n].some((i) => i.checked));
    },

    // También se llama en keyup: las escalas cambian la selección con las
    // flechas de forma programática, sin disparar el evento change.
    revisar() {
        this.respondidas = this.pasos().map((el) => this.pasoRespondido(el));
    },

    get totalRespondidas() {
        return this.respondidas.filter(Boolean).length;
    },

    get completo() {
        return this.respondidas.length === this.total && this.respondidas.every(Boolean);
    },

    get esUltimo() {
        return this.paso === this.total - 1;
    },

    primerPendiente() {
        const i = this.respondidas.findIndex((r) => !r);
        return i === -1 ? null : i;
    },

    // Se puede volver a cualquier paso anterior y avanzar hasta el primero
    // sin responder: no se salta una pregunta obligatoria sin verla.
    puedeIrA(i) {
        return i <= this.paso || this.respondidas.slice(0, i).every(Boolean);
    },

    irA(i, { enfocar = true } = {}) {
        if (i < 0 || i >= this.total) return;
        clearTimeout(this.temporizador);
        if (i !== this.paso) {
            this.direccion = i > this.paso ? 1 : -1;
            this.paso = i;
        }
        this.aviso = null;

        this.$nextTick(() => {
            const inicio = this.$refs.inicio;
            if (inicio) {
                const tope = inicio.getBoundingClientRect().top + window.scrollY - 96;
                if (window.scrollY > tope) {
                    window.scrollTo({ top: tope, behavior: sinMovimiento() ? 'auto' : 'smooth' });
                }
            }
            if (enfocar) {
                document.getElementById(`paso-${i}-titulo`)?.focus({ preventScroll: true });
            }
        });
    },

    siguiente() {
        this.revisar();
        if (!this.respondidas[this.paso]) {
            this.avisar('Responde esta pregunta para continuar.');
            return;
        }
        this.irA(this.paso + 1);
    },

    anterior() {
        this.irA(this.paso - 1);
    },

    avisar(mensaje) {
        this.aviso = mensaje;
        this.sacudir = false;
        this.$nextTick(() => { this.sacudir = true; });
    },

    alCambiar(evento) {
        this.sucio = true;
        this.revisar();
        if (this.respondidas[this.paso]) this.aviso = null;

        // Avance automático: solo preguntas de respuesta única (escala y
        // single), solo tras un toque/clic y nunca desde el último paso.
        const input = evento.target;
        const pasoEl = input.closest('[data-paso]');
        const tipo = pasoEl?.dataset.tipo;
        if (input.type !== 'radio' || !['escala', 'single'].includes(tipo) || !fuePuntero()) return;

        clearTimeout(this.temporizador);
        const pasoActual = this.paso;
        if (this.esUltimo) return;
        this.temporizador = setTimeout(() => {
            if (this.paso === pasoActual && this.respondidas[pasoActual]) {
                this.irA(pasoActual + 1);
            }
        }, 380);
    },

    // Enter dentro de la encuesta avanza de pregunta en vez de enviar la
    // sección completa a mitad de camino.
    alEnter(evento) {
        if (evento.target.tagName === 'TEXTAREA' || evento.target.tagName === 'BUTTON' || evento.target.tagName === 'A') return;
        if (!this.esUltimo) {
            evento.preventDefault();
            this.siguiente();
        }
    },

    // Sustituye al `required` nativo, que con preguntas en pasos ocultos
    // bloquea el envío sin mostrar nada: lleva a la primera pendiente.
    enviar(evento) {
        this.revisar();
        const pendiente = this.primerPendiente();
        if (pendiente !== null) {
            evento.preventDefault();
            this.irA(pendiente);
            this.$nextTick(() => this.avisar('Esta pregunta aún no tiene respuesta.'));
            return;
        }
        this.enviando = true;
    },

    // Aviso del navegador si se sale de la sección con respuestas sin guardar.
    alSalir(evento) {
        if (this.sucio && !this.enviando) {
            evento.preventDefault();
            evento.returnValue = '';
        }
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
