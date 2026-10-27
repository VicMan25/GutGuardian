<x-layouts.estudiante :titulo="$seccion->nombre" ancho="amplio" enfoque>

    <x-slot:progreso>
        <x-barra-progreso
            :seccion-actual="$seccionActual"
            :total-secciones="$totalSecciones"
            :pregunta-actual="1"
            :total-preguntas="$seccion->preguntas->count()"
            :nombre-seccion="$seccion->nombre"
        />
    </x-slot:progreso>

    @php
        $esSociodemografica = $seccion->preguntas->contains(fn ($p) => str_starts_with($p->codigo, 'SD'));
        $campos = $campos->values();
        $total = $campos->count();

        // Estado de cada pregunta según lo que llega del servidor (guardado o
        // old() tras un error). Es la misma regla que aplica encuestaGuiada en
        // el navegador; se calcula aquí para que el HTML ya abra en el paso correcto.
        $estado = $campos->map(function ($campo) use ($errors) {
            $id = $campo['pregunta']->id;

            return [
                'respondida' => match ($campo['tipo']) {
                    'escala'   => $campo['valor'] !== null,
                    'single'   => $campo['seleccionado'] !== null,
                    'multiple' => count($campo['seleccionados']) > 0,
                    'matriz'   => count($campo['respuestas']) >= count($campo['items']),
                },
                'error' => $errors->has("respuestas.{$id}") || $errors->has("respuestas.{$id}.*"),
            ];
        });

        // Paso inicial: la primera pregunta con error de validación; si no hay,
        // la primera sin responder (reanudar); si todo está respondido, la primera.
        $pasoInicial = $estado->search(fn ($e) => $e['error']);
        if ($pasoInicial === false) {
            $pasoInicial = $estado->search(fn ($e) => ! $e['respondida']);
        }
        $pasoInicial = $pasoInicial === false ? 0 : $pasoInicial;

        // Enunciado corto para el índice lateral: sin la aclaración entre paréntesis.
        $enunciadoCorto = fn ($pregunta) => \Illuminate\Support\Str::limit(
            trim(preg_replace('/\s*\(.*\)\s*$/u', '', $pregunta->enunciado)), 58
        );
    @endphp

    {{--
        Encuesta guiada: una pregunta por paso. Es solo presentación —
        sigue siendo UN formulario por sección con todas las preguntas
        (los pasos ocultos también se envían) y el servidor valida y guarda
        igual que antes. `novalidate`: el `required` nativo no puede
        mostrarse en un paso oculto y bloquearía el envío en silencio;
        encuestaGuiada.enviar() lleva a la pregunta pendiente y la
        validación del servidor sigue siendo la red de seguridad.
    --}}
    <form method="POST"
          action="{{ route('encuesta.guardar', [$diligenciamiento, $seccionActual]) }}"
          novalidate
          x-data="encuestaGuiada({ total: {{ $total }}, inicial: {{ $pasoInicial }} })"
          @change="alCambiar($event)"
          @keyup="revisar()"
          @keydown.enter="alEnter($event)"
          @submit="enviar($event)"
          @beforeunload.window="alSalir($event)">
        @csrf

        <div class="grid lg:grid-cols-[260px_minmax(0,1fr)] gap-6 lg:gap-10 items-start">

            {{-- ========================================================
                 Índice de la sección — desktop. Aprovecha el ancho para
                 mostrar el mapa completo: qué falta, dónde estoy, volver.
            ======================================================== --}}
            <aside class="hidden lg:block sticky top-36">
                <p class="gg-rotulo">Sección {{ $seccionActual }} de {{ $totalSecciones }}</p>
                <h1 class="mt-1 font-display text-2xl font-medium text-gg-tinta">{{ $seccion->nombre }}</h1>
                <p class="text-sm text-gg-tinta-suave mt-2 leading-relaxed">
                    Todas las preguntas de esta sección son obligatorias.
                    @if($esSociodemografica)
                        Ya completamos estas respuestas con los datos de tu registro — revísalas y ajústalas si algo cambió.
                    @endif
                </p>

                <ol class="mt-6 space-y-0.5" aria-label="Preguntas de la sección">
                    @foreach($campos as $i => $campo)
                    <li>
                        <button type="button"
                                @click="puedeIrA({{ $i }}) && irA({{ $i }})"
                                :disabled="!puedeIrA({{ $i }})"
                                :aria-current="paso === {{ $i }} ? 'step' : null"
                                class="w-full flex items-start gap-3 px-2.5 py-2 rounded-control text-left text-sm transition-colors
                                       disabled:cursor-not-allowed"
                                :class="paso === {{ $i }}
                                    ? 'bg-gg-superficie shadow-elev-1 text-gg-tinta'
                                    : (puedeIrA({{ $i }}) ? 'text-gg-tinta-suave hover:bg-gg-papel-hondo hover:text-gg-tinta' : 'text-[#A3ACA6]')">
                            <span class="mt-px shrink-0 w-5 h-5 rounded-full inline-flex items-center justify-center text-[11px] font-mono border transition-colors"
                                  :class="respondidas[{{ $i }}]
                                      ? 'bg-gg-primario border-gg-primario text-white'
                                      : (paso === {{ $i }} ? 'border-gg-primario text-gg-primario' : 'border-[#C7D0C9]')">
                                <x-icono nombre="check" class="w-3 h-3" x-show="respondidas[{{ $i }}]" />
                                <span x-show="!respondidas[{{ $i }}]">{{ $i + 1 }}</span>
                            </span>
                            <span class="leading-snug">{{ $enunciadoCorto($campo['pregunta']) }}</span>
                        </button>
                    </li>
                    @endforeach
                </ol>
            </aside>

            {{-- ========================================================
                 Columna de la pregunta
            ======================================================== --}}
            <div class="min-w-0 max-w-[680px] w-full mx-auto lg:mx-0" x-ref="inicio">

                {{-- Encabezado compacto — móvil y tablet --}}
                <div class="lg:hidden mb-4">
                    <h1 class="font-display text-2xl font-medium text-gg-tinta">{{ $seccion->nombre }}</h1>
                    <p class="text-sm text-gg-tinta-suave mt-1">
                        Todas las preguntas de esta sección son obligatorias.
                        @if($esSociodemografica)
                            Ya completamos estas respuestas con los datos de tu registro — revísalas y ajústalas si algo cambió.
                        @endif
                    </p>
                </div>

                @if($errors->any())
                    <x-alerta tipo="error" titulo="Revisa las preguntas marcadas" class="mb-4">
                        Hay respuestas pendientes o incompletas en esta sección. Te llevamos a la primera; las demás aparecen marcadas en el progreso.
                    </x-alerta>
                @endif

                {{-- Progreso por pregunta: segmentos tocables --}}
                <div class="mb-4">
                    <div class="flex items-baseline justify-between gap-3 mb-2">
                        <p class="text-sm text-gg-tinta" aria-live="polite">
                            Pregunta <span class="font-medium" x-text="paso + 1">{{ $pasoInicial + 1 }}</span> de {{ $total }}
                        </p>
                        <p class="text-sm text-gg-tinta-suave">
                            <span x-show="!completo"><span x-text="totalRespondidas">{{ $estado->where('respondida', true)->count() }}</span> respondidas</span>
                            <span x-show="completo" x-cloak class="inline-flex items-center gap-1 font-medium text-gg-primario animate-gg-entrada">
                                <x-icono nombre="check" class="w-4 h-4" /> Sección completa
                            </span>
                        </p>
                    </div>
                    <div class="flex gap-1" aria-hidden="true">
                        @foreach($campos as $i => $campo)
                        <button type="button" tabindex="-1"
                                @click="puedeIrA({{ $i }}) && irA({{ $i }}, { enfocar: false })"
                                class="group flex-1 py-2 -my-2"
                                :class="puedeIrA({{ $i }}) ? 'cursor-pointer' : 'cursor-not-allowed'">
                            {{-- Sin color estático: Alpine conserva las clases del atributo
                                 class y dos bg-* en conflicto dependerían del orden del CSS. --}}
                            <span class="block h-1.5 rounded-full transition-colors duration-300"
                                  :class="paso === {{ $i }}
                                      ? 'bg-gg-primario'
                                      : (respondidas[{{ $i }}] ? 'bg-[#8DB3A2]' : '{{ $estado[$i]['error'] ? 'bg-[#D9A898]' : 'bg-gg-borde' }}')"></span>
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Pasos: una pregunta cada uno. overflow-x-clip: la transición
                     entra desplazada en horizontal y, sin recorte, en móvil
                     ensancha un instante el viewport (tirón lateral). clip, a
                     diferencia de hidden, no crea un contenedor de scroll. --}}
                <div class="overflow-x-clip -mx-2 px-2 pb-2">
                @foreach($campos as $i => $campo)
                @php
                    $nombreCampo = "respuestas[{$campo['pregunta']->id}]";
                    // Para 'matriz' el error real vive en una clave anidada
                    // (respuestas.{pregunta}.{item}, ej. falta un ítem de la tabla),
                    // no en la clave del campo completo: sin este fallback el mensaje
                    // nunca se mostraba y la sección parecía no dejar avanzar sin explicar por qué.
                    $errorCampo = $errors->first('respuestas.'.$campo['pregunta']->id)
                        ?: ($errors->has('respuestas.'.$campo['pregunta']->id.'.*')
                            ? 'Falta responder uno o más ítems de esta pregunta.'
                            : null);
                @endphp
                <section
                    data-paso="{{ $i }}"
                    data-tipo="{{ $campo['tipo'] }}"
                    x-show="paso === {{ $i }}"
                    @if($i !== $pasoInicial) style="display: none" @endif
                    :class="direccion > 0 ? 'gg-paso-adelante' : 'gg-paso-atras'"
                    aria-labelledby="paso-{{ $i }}-titulo"
                >
                    <x-tarjeta padding="p-5 sm:p-8" class="rounded-bloque shadow-elev-2 {{ $estado[$i]['error'] ? '!border-[#D9A898]' : '' }}"
                               x-bind:class="sacudir && paso === {{ $i }} && aviso ? 'gg-sacudir' : ''">
                        <p id="paso-{{ $i }}-titulo" tabindex="-1" class="flex items-center gap-2 mb-3 focus:outline-none">
                            <span class="font-mono text-xs text-gg-primario bg-gg-primario-suave rounded-full px-2 py-0.5">{{ $campo['pregunta']->codigo }}</span>
                            <span class="text-sm text-gg-tinta-suave">Pregunta {{ $i + 1 }} de {{ $total }}</span>
                        </p>

                        @switch($campo['tipo'])
                            @case('escala')
                                @if($campo['pregunta']->codigo === 'P13')
                                    <x-escala-dolor
                                        :nombre="$nombreCampo"
                                        :codigo="$campo['pregunta']->codigo"
                                        :pregunta="$campo['pregunta']->enunciado"
                                        :valor="$campo['valor']"
                                        :requerido="true"
                                        :error="$errorCampo"
                                        destacada
                                    />
                                @else
                                    <x-escala-frecuencia
                                        :nombre="$nombreCampo"
                                        :codigo="$campo['pregunta']->codigo"
                                        :pregunta="$campo['pregunta']->enunciado"
                                        :valor="$campo['valor']"
                                        :requerido="true"
                                        :error="$errorCampo"
                                        destacada
                                    />
                                @endif
                                @break

                            @case('single')
                                <x-selector-unico
                                    :nombre="$nombreCampo"
                                    :codigo="$campo['pregunta']->codigo"
                                    :pregunta="$campo['pregunta']->enunciado"
                                    :opciones="$campo['opciones']"
                                    :seleccionado="$campo['seleccionado']"
                                    :requerido="true"
                                    :error="$errorCampo"
                                    destacada
                                />
                                @break

                            @case('multiple')
                                <x-opcion-multiple
                                    :nombre="$nombreCampo"
                                    :codigo="$campo['pregunta']->codigo"
                                    :pregunta="$campo['pregunta']->enunciado"
                                    :opciones="$campo['opciones']"
                                    :seleccionados="$campo['seleccionados']"
                                    :requerido="true"
                                    :error="$errorCampo"
                                    destacada
                                />
                                @break

                            @case('matriz')
                                <x-matriz-sintomas
                                    :nombre="$nombreCampo"
                                    :codigo="$campo['pregunta']->codigo"
                                    :pregunta="$campo['pregunta']->enunciado"
                                    :items="$campo['items']"
                                    :opciones="$campo['opciones']"
                                    :respuestas="$campo['respuestas']"
                                    :requerido="true"
                                    :error="$errorCampo"
                                    destacada
                                />
                                @break
                        @endswitch

                        {{-- Aviso del navegador: pregunta sin responder al intentar avanzar --}}
                        <p class="mt-4 flex items-center gap-2 text-sm font-medium text-gg-riesgo-alto"
                           role="alert"
                           x-show="aviso && paso === {{ $i }}" x-cloak>
                            <x-icono nombre="alertas" class="w-4 h-4" />
                            <span x-text="aviso"></span>
                        </p>

                        {{-- Pista de interacción según el tipo --}}
                        @if(in_array($campo['tipo'], ['escala', 'single']) && $i < $total - 1)
                            <p class="mt-4 text-xs text-gg-tinta-suave hidden sm:block">
                                Al elegir una opción pasamos a la siguiente pregunta. También puedes usar las flechas y Enter.
                            </p>
                        @endif
                    </x-tarjeta>
                </section>
                @endforeach
                </div>

                {{-- ====================================================
                     Barra de acciones — fija abajo, al alcance del pulgar
                ==================================================== --}}
                <div class="sticky bottom-[calc(env(safe-area-inset-bottom)+0.75rem)] md:bottom-4 z-20 mt-5">
                    <div class="flex items-center gap-2 p-2 rounded-[18px] bg-gg-superficie/90 backdrop-blur-md border border-gg-borde shadow-elev-3">

                        {{-- Anterior (dentro de la sección) --}}
                        <x-boton variante="fantasma" icono="atras" x-show="paso > 0" x-cloak x-on:click="anterior()">
                            Anterior
                        </x-boton>

                        {{-- Atrás (sección anterior), solo en la primera pregunta --}}
                        @if($seccionActual > 1)
                            <x-boton variante="fantasma" href="{{ route('encuesta.seccion', [$diligenciamiento, $seccionActual - 1]) }}" icono="atras"
                                     x-show="paso === 0" :style="$pasoInicial !== 0 ? 'display: none' : null">
                                Atrás
                            </x-boton>
                        @else
                            <span x-show="paso === 0" class="hidden sm:block pl-3 text-sm text-gg-tinta-suave"
                                  @if($pasoInicial !== 0) style="display: none" @endif>
                                Tus respuestas se guardan por sección.
                            </span>
                        @endif

                        <span class="flex-1"></span>

                        {{-- Siguiente pregunta --}}
                        <x-boton variante="primario" icono-final="flecha" class="flex-1 sm:flex-none"
                                 x-show="!esUltimo" x-on:click="siguiente()"
                                 :style="$pasoInicial === $total - 1 ? 'display: none' : null">
                            Siguiente
                        </x-boton>

                        {{-- Envío de la sección (último paso) --}}
                        <x-boton variante="primario" tipo="submit" icono-final="flecha" class="flex-1 sm:flex-none"
                                 x-show="esUltimo" x-bind:class="completo && 'gg-listo'"
                                 :style="$pasoInicial !== $total - 1 ? 'display: none' : null">
                            {{ $esUltima ? 'Continuar a confirmación' : 'Siguiente sección' }}
                        </x-boton>
                    </div>
                </div>

            </div>
        </div>
    </form>

</x-layouts.estudiante>
