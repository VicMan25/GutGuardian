{{--
    Selección única (radios) para preguntas tipo 'single' con opciones
    propias — SD1-SD4, P08. No usar para P01-P07/P10/P19 (ver
    escala-frecuencia) ni P13 (ver escala-dolor), que tienen su propia
    escala fija.

    Props:
      nombre     string   name="{{ $nombre }}", value="opcion_id"
      codigo     string   Código de pregunta
      pregunta   string   Enunciado
      opciones   array    [['id' => int, 'etiqueta' => string], ...]
      seleccionado int|null  ID de la opción ya guardada
      requerido  bool
      error      string
--}}
@props([
    'nombre',
    'codigo'      => '',
    'pregunta'    => '',
    'opciones'    => [],
    'seleccionado'=> null,
    'requerido'   => false,
    'error'       => null,
])

@php
$uid = 'su-' . Str::random(8);
@endphp

<div
    x-data="selectorUnico({{ $seleccionado ?? 'null' }})"
    role="radiogroup"
    aria-labelledby="{{ $uid }}-label"
    aria-required="{{ $requerido ? 'true' : 'false' }}"
>
    @if($pregunta)
    <p id="{{ $uid }}-label" class="text-sm font-medium text-gg-tinta leading-snug mb-3">
        @if($codigo)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>
    @endif

    <div class="space-y-2">
        @foreach($opciones as $opcion)
        <label
            class="flex items-center gap-3 p-3 rounded-control border cursor-pointer select-none
                   transition-colors duration-100 group"
            :class="marcado({{ $opcion['id'] }})
                ? 'border-gg-primario bg-gg-primario-suave'
                : 'border-gg-borde bg-gg-superficie hover:bg-gg-papel'"
        >
            <input
                type="radio"
                name="{{ $nombre }}"
                value="{{ $opcion['id'] }}"
                class="sr-only"
                {{ $requerido ? 'required' : '' }}
                :checked="marcado({{ $opcion['id'] }})"
                @change="seleccionar({{ $opcion['id'] }})"
            />

            <span
                class="w-4 h-4 shrink-0 rounded-full border-2 flex items-center justify-center transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }})
                    ? 'border-gg-primario'
                    : 'border-gg-borde group-hover:border-gg-primario'"
                aria-hidden="true"
            >
                <span
                    class="w-2 h-2 rounded-full bg-gg-primario transition-opacity duration-100"
                    :class="marcado({{ $opcion['id'] }}) ? 'opacity-100' : 'opacity-0'"
                ></span>
            </span>

            <span
                class="text-sm leading-snug transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }}) ? 'text-gg-primario font-medium' : 'text-gg-tinta'"
            >{{ $opcion['etiqueta'] }}</span>
        </label>
        @endforeach
    </div>

    @if($error)
    <p class="mt-2 text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif
</div>
