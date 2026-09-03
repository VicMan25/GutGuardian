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

    <form method="POST" action="{{ route('encuesta.guardar', [$diligenciamiento, $seccionActual]) }}" class="space-y-8">
        @csrf

        @php
            $esSociodemografica = $seccion->preguntas->contains(fn ($p) => str_starts_with($p->codigo, 'SD'));
        @endphp

        <div>
            <h1 class="font-display text-2xl font-medium text-gg-tinta">{{ $seccion->nombre }}</h1>
            <p class="text-sm text-gg-tinta-suave mt-1">
                Todas las preguntas de esta sección son obligatorias.
                @if($esSociodemografica)
                    Ya completamos estas respuestas con los datos de tu registro — revísalas y ajústalas si algo cambió.
                @endif
            </p>
        </div>

        @foreach($campos as $campo)
        <x-tarjeta>
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

        <div class="flex items-center justify-between gap-4 pb-4">
            @if($seccionActual > 1)
                <x-boton variante="secundario" href="{{ route('encuesta.seccion', [$diligenciamiento, $seccionActual - 1]) }}">
                    Atrás
                </x-boton>
            @else
                <span></span>
            @endif

            <x-boton variante="primario" tipo="submit">
                {{ $esUltima ? 'Continuar a confirmación' : 'Siguiente sección' }}
            </x-boton>
        </div>
    </form>

</x-layouts.estudiante>
