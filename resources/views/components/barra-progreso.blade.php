{{--
    Barra de progreso por sección — muestra dónde está el estudiante en el instrumento.
    Diseñada para el layout estudiante (max-w-640px).

    La unidad de progreso es la SECCIÓN (no la pregunta individual),
    porque el instrumento tiene sentido narrativo por secciones:
    Sociodemográfico → Hábitos → Clínico.

    Props:
      seccionActual   int    Sección activa: 1, 2 o 3
      totalSecciones  int    Total de secciones (3)
      preguntaActual  int    Pregunta activa dentro de la sección
      totalPreguntas  int    Total de preguntas en la sección
      nombreSeccion   string Nombre de la sección actual
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
        <p class="text-xs font-medium text-gg-tinta truncate">
            @if($nombreSeccion)
                {{ $nombreSeccion }}
            @else
                Sección {{ $seccionActual }}
            @endif
        </p>
        <span class="text-2xs text-gg-tinta-suave whitespace-nowrap shrink-0">
            <span class="sr-only">Sección</span>
            {{ $seccionActual }} de {{ $totalSecciones }}
        </span>
    </div>

    {{-- Pista de secciones + puntos de preguntas --}}
    <div class="flex items-center gap-1.5" aria-hidden="true">

        @for($s = 1; $s <= $totalSecciones; $s++)

        {{-- Segmento de sección --}}
        @if($s < $seccionActual)
            {{-- Sección completada --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-primario"></div>
        @elseif($s === $seccionActual)
            {{-- Sección activa: barra de progreso interno --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-borde overflow-hidden">
                <div
                    class="h-full rounded-full bg-gg-primario transition-all duration-300"
                    style="width: {{ max(4, $seccionPct) }}%"
                ></div>
            </div>
        @else
            {{-- Sección futura --}}
            <div class="h-1.5 flex-1 rounded-full bg-gg-borde"></div>
        @endif

        {{-- Separador entre secciones --}}
        @if($s < $totalSecciones)
        <div class="w-1.5 h-1.5 rounded-full shrink-0
                    {{ $s < $seccionActual ? 'bg-gg-primario' : 'bg-gg-borde' }}">
        </div>
        @endif

        @endfor

    </div>

    {{-- Texto accesible oculto para lectores de pantalla --}}
    <p class="sr-only">
        Sección {{ $seccionActual }} de {{ $totalSecciones }}:
        {{ $nombreSeccion }}.
        Pregunta {{ $preguntaActual }} de {{ $totalPreguntas }}.
    </p>

</div>
