{{--
    Barra de progreso por bloque — muestra dónde está el estudiante en el instrumento.
    Diseñada para el layout estudiante (max-w-640px).

    La unidad de progreso es el BLOQUE (no la pregunta individual). En la
    encuesta por jornadas el bloque es el día (1 a 3); el nombre de las props
    se conserva de cuando el bloque era la sección temática.

    Props:
      seccionActual   int    Bloque activo: 1, 2 o 3
      totalSecciones  int    Total de bloques (3)
      preguntaActual  int    Pregunta activa dentro de la sección
      totalPreguntas  int    Total de preguntas en la sección
      nombreSeccion   string Nombre del bloque actual (p. ej. «Día 2 de 3»)
--}}
@props([
    'seccionActual'  => 1,
    'totalSecciones' => 3,
    'preguntaActual' => 1,
    'totalPreguntas' => 10,
    'nombreSeccion'  => '',
])

@php
$seccionPct = round(($preguntaActual - 1) / max($totalPreguntas, 1) * 100);
@endphp

<div class="w-full" aria-label="Progreso del instrumento" role="navigation">

    {{-- Sección actual --}}
    <div class="flex items-baseline justify-between gap-2 mb-2">
        <p class="text-sm font-medium text-gg-tinta truncate">
            @if($nombreSeccion)
                {{ $nombreSeccion }}
            @else
                Sección {{ $seccionActual }}
            @endif
        </p>
        <span class="font-mono text-xs text-gg-tinta-suave whitespace-nowrap shrink-0">
            <span class="sr-only">Bloque</span>
            {{ $seccionActual }}/{{ $totalSecciones }}
        </span>
    </div>

    {{-- Pista de secciones + puntos de preguntas --}}
    <div class="flex items-center gap-1" aria-hidden="true">

        @for($s = 1; $s <= $totalSecciones; $s++)

        {{-- Segmento de sección --}}
        @if($s < $seccionActual)
            {{-- Sección completada --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-primario"></div>
        @elseif($s === $seccionActual)
            {{-- Sección activa: barra de progreso interno --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-acento overflow-hidden">
                <div
                    class="h-full rounded-full bg-gg-primario transition-all duration-500"
                    style="width: {{ max(4, $seccionPct) }}%"
                ></div>
            </div>
        @else
            {{-- Sección futura --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-borde"></div>
        @endif


        @endfor

    </div>

    {{-- Texto accesible oculto para lectores de pantalla --}}
    <p class="sr-only">
        {{ $nombreSeccion ?: 'Bloque '.$seccionActual.' de '.$totalSecciones }}.
        Pregunta {{ $preguntaActual }} de {{ $totalPreguntas }}.
    </p>

</div>
