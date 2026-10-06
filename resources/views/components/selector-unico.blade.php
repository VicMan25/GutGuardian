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
      destacada  bool     Enunciado grande (encuesta guiada); el código lo muestra el paso
--}}
@props([
    'nombre',
    'codigo'      => '',
    'pregunta'    => '',
    'opciones'    => [],
    'seleccionado'=> null,
    'requerido'   => false,
    'error'       => null,
    'destacada'   => false,
])

@php
$uid = 'su-' . Str::random(8);

// Opciones cortas (semestres, tiempos de comida, rangos de edad) caben en
// mosaicos de 2-3 columnas; las largas (programas) en 1 columna en móvil.
// Así SD4 (10 opciones) o P08 (7) no ocupan media pantalla en vertical.
$cortas = collect($opciones)->every(fn ($o) => mb_strlen($o['etiqueta']) <= 18);
$rejilla = $cortas ? 'grid grid-cols-2 sm:grid-cols-3 gap-2' : 'grid grid-cols-1 sm:grid-cols-2 gap-2';
@endphp

<div
    x-data="selectorUnico({{ $seleccionado ?? 'null' }})"
    role="radiogroup"
    aria-labelledby="{{ $uid }}-label"
    aria-required="{{ $requerido ? 'true' : 'false' }}"
>
    @if($pregunta)
    <p id="{{ $uid }}-label" class="{{ $destacada ? 'font-display text-xl sm:text-2xl font-medium text-gg-tinta leading-snug mb-5' : 'text-base font-medium text-gg-tinta leading-snug mb-3' }}">
        @if($codigo && ! $destacada)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>
    @endif

    <div class="{{ $rejilla }}">
        @foreach($opciones as $opcion)
        <label
            class="flex items-center gap-3 min-h-[48px] px-3.5 py-3 rounded-control border cursor-pointer select-none
                   transition-[background-color,border-color,box-shadow] duration-150 group"
            :class="marcado({{ $opcion['id'] }})
                ? 'border-gg-primario bg-gg-primario-suave shadow-[inset_0_0_0_1px_var(--gg-primario)]'
                : 'border-gg-borde bg-gg-superficie hover:border-[#C7D0C9] hover:bg-gg-papel'"
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
                class="w-5 h-5 shrink-0 rounded-full border-2 flex items-center justify-center transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }})
                    ? 'border-gg-primario'
                    : 'border-gg-borde group-hover:border-gg-primario'"
                aria-hidden="true"
            >
                <span
                    class="w-2.5 h-2.5 rounded-full bg-gg-primario transition-opacity duration-100"
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
    <p class="mt-2 text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif
</div>
