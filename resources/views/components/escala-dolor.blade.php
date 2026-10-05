{{--
    Escala de dolor abdominal 1-5 — variante del selector segmentado para P13.
    Misma gramática visual que escala-frecuencia; 5 segmentos en lugar de 4.

    Props:
      nombre     string   Atributo name del input
      codigo     string   Código de pregunta (P13)
      pregunta   string   Enunciado
      valor      int|null Valor seleccionado (1-5), null si sin respuesta
      requerido  bool
      error      string
--}}
@props([
    'nombre',
    'codigo'   => 'P13',
    'pregunta' => '¿Cómo califica la intensidad del dolor abdominal presentado en los últimos 6 meses?',
    'valor'    => null,
    'requerido'=> false,
    'error'    => null,
])

@php
$uid = 'ed-' . Str::random(8);

$opciones = [
    ['valor' => 1, 'etiqueta' => '1',  'desc' => 'Sin dolor'],
    ['valor' => 2, 'etiqueta' => '2',  'desc' => 'Leve'],
    ['valor' => 3, 'etiqueta' => '3',  'desc' => 'Moderado'],
    ['valor' => 4, 'etiqueta' => '4',  'desc' => 'Intenso'],
    ['valor' => 5, 'etiqueta' => '5',  'desc' => 'Muy intenso'],
];
@endphp

<div
    x-data="escalaDolor({{ $valor ?? 'null' }})"
    role="radiogroup"
    aria-labelledby="{{ $uid }}-label"
    aria-required="{{ $requerido ? 'true' : 'false' }}"
    @keydown.arrow-right.prevent="siguiente()"
    @keydown.arrow-left.prevent="anterior()"
    @keydown.arrow-down.prevent="siguiente()"
    @keydown.arrow-up.prevent="anterior()"
>

    <p id="{{ $uid }}-label" class="text-base font-medium text-gg-tinta leading-snug mb-2">
        @if($codigo)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>

    {{-- Leyenda extremos --}}
    <div class="flex justify-between mb-2 px-0.5">
        <span class="text-xs text-gg-tinta-suave">1 · Sin dolor</span>
        <span class="text-xs text-gg-tinta-suave">Muy intenso · 5</span>
    </div>

    <div class="grid grid-cols-5 gap-1.5 p-1.5 rounded-[14px] bg-gg-papel border border-gg-borde"
         role="presentation">

        @foreach($opciones as $i => $opcion)
        <label
            class="relative cursor-pointer select-none rounded-control transition-[background-color,box-shadow,transform] duration-150 active:scale-[0.97]"
            :class="seleccionado === {{ $opcion['valor'] }}
                ? 'gg-seg-activo'
                : 'bg-gg-superficie hover:shadow-elev-1'"
        >
            <input
                type="radio"
                name="{{ $nombre }}"
                value="{{ $opcion['valor'] }}"
                class="sr-only"
                {{ $requerido ? 'required' : '' }}
                :checked="seleccionado === {{ $opcion['valor'] }}"
                @change="seleccionar({{ $opcion['valor'] }})"
                @focus="enfocado = {{ $opcion['valor'] }}"
                @blur="enfocado = null"
            />

            <span
                class="pointer-events-none absolute inset-0 rounded-control transition-opacity duration-100"
                :class="enfocado === {{ $opcion['valor'] }} ? 'outline outline-2 outline-offset-2 outline-gg-primario' : 'opacity-0'"
                aria-hidden="true"
            ></span>

            <div class="flex flex-col items-center justify-center gap-1 py-2.5 px-1 text-center min-h-[4rem]">
                <span
                    class="font-display text-xl font-medium leading-none transition-colors duration-100"
                    :class="seleccionado === {{ $opcion['valor'] }} ? 'text-gg-primario' : 'text-gg-tinta'"
                >{{ $opcion['etiqueta'] }}</span>
                <span class="hidden sm:block text-2xs text-gg-tinta-suave leading-tight">
                    {{ $opcion['desc'] }}
                </span>
            </div>
        </label>
        @endforeach

    </div>

    @if($error)
    <p class="mt-2 text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
