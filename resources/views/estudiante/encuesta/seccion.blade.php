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

        <div>
            <h1 class="font-display text-2xl font-medium text-gg-tinta">{{ $seccion->nombre }}</h1>
            <p class="text-sm text-gg-tinta-suave mt-1">
                Todas las preguntas de esta sección son obligatorias.
            </p>
        </div>

        @foreach($campos as $campo)
        <x-tarjeta>
            @php
                $nombreCampo = "respuestas[{$campo['pregunta']->id}]";
                $errorCampo = $errors->first('respuestas.'.$campo['pregunta']->id);
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
