<x-layouts.estudiante :titulo="$seccion->nombre">

    <x-slot:progreso>
        <x-barra-progreso
            :seccion-actual="$seccionActual"
            :total-secciones="$totalSecciones"
            :pregunta-actual="1"
            :total-preguntas="$seccion->preguntas->count()"
            :nombre-seccion="$seccion->nombre"
        />
    </x-slot:progreso>

    <form method="POST" action="{{ route('encuesta.guardar', [$diligenciamiento, $seccionActual]) }}" class="space-y-5">
        @csrf

        @php
            $esSociodemografica = $seccion->preguntas->contains(fn ($p) => str_starts_with($p->codigo, 'SD'));
        @endphp

        <div class="animate-gg-entrada">
            <p class="gg-rotulo">Sección {{ $seccionActual }} de {{ $totalSecciones }} · {{ $seccion->preguntas->count() }} preguntas</p>
            <h1 class="mt-1 font-display text-3xl font-medium text-gg-tinta">{{ $seccion->nombre }}</h1>
            <p class="text-base text-gg-tinta-suave mt-2">
                Todas las preguntas de esta sección son obligatorias.
                @if($esSociodemografica)
                    Ya completamos estas respuestas con los datos de tu registro — revísalas y ajústalas si algo cambió.
                @endif
            </p>
        </div>

        @if($errors->any())
            <x-alerta tipo="error" titulo="Revisa las preguntas marcadas">
                Hay respuestas pendientes o incompletas en esta sección. Te indicamos cuáles debajo de cada pregunta.
            </x-alerta>
        @endif

        @foreach($campos as $campo)
        <x-tarjeta class="animate-gg-entrada scroll-mt-40 {{ ($errors->has('respuestas.'.$campo['pregunta']->id) || $errors->has('respuestas.'.$campo['pregunta']->id.'.*')) ? '!border-[#D9A898]' : '' }}"
                   style="animation-delay: {{ min($loop->index, 6) * 40 }}ms">
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
                        />
                    @else
                        <x-escala-frecuencia
                            :nombre="$nombreCampo"
                            :codigo="$campo['pregunta']->codigo"
                            :pregunta="$campo['pregunta']->enunciado"
                            :valor="$campo['valor']"
                            :requerido="true"
                            :error="$errorCampo"
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
                    />
                    @break
            @endswitch
        </x-tarjeta>
        @endforeach

        {{-- Barra de acciones: queda fija sobre la navegación inferior en móvil --}}
        <div class="sticky bottom-[calc(4rem+env(safe-area-inset-bottom)+0.75rem)] md:bottom-4 z-20 pt-2">
            <div class="flex items-center justify-between gap-3 p-2 rounded-[18px] bg-gg-superficie/90 backdrop-blur-md border border-gg-borde shadow-elev-3">
                @if($seccionActual > 1)
                    <x-boton variante="fantasma" href="{{ route('encuesta.seccion', [$diligenciamiento, $seccionActual - 1]) }}" icono="atras">
                        Atrás
                    </x-boton>
                @else
                    <span class="pl-3 text-sm text-gg-tinta-suave">Tus respuestas se guardan por sección.</span>
                @endif

                <x-boton variante="primario" tipo="submit" icono-final="flecha" class="flex-1 sm:flex-none">
                    {{ $esUltima ? 'Continuar a confirmación' : 'Siguiente sección' }}
                </x-boton>
            </div>
        </div>
    </form>

</x-layouts.estudiante>
