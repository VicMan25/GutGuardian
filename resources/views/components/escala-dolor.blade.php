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

    <p id="{{ $uid }}-label" class="text-sm font-medium text-gg-tinta leading-snug mb-1">
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
        <span class="text-2xs text-gg-tinta-suave">Sin dolor</span>
        <span class="text-2xs text-gg-tinta-suave">Muy intenso</span>
    </div>

    <div class="grid grid-cols-5 border border-gg-borde rounded-control overflow-hidden"
         role="presentation">

        @foreach($opciones as $i => $opcion)
        <label
            class="relative cursor-pointer select-none transition-colors duration-100"
            :class="{
                'gg-seg-activo': seleccionado === {{ $opcion['valor'] }},
                'hover:bg-gg-papel': seleccionado !== {{ $opcion['valor'] }},
            }"
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

            @if($i < 4)
            <span class="absolute right-0 top-0 h-full w-px bg-gg-borde" aria-hidden="true"
                :class="(seleccionado === {{ $opcion['valor'] }} || seleccionado === {{ $opciones[$i+1]['valor'] }}) ? 'opacity-0' : 'opacity-100'">
            </span>
            @endif

            <span
                class="pointer-events-none absolute inset-0 transition-opacity duration-100"
                :class="enfocado === {{ $opcion['valor'] }} ? 'shadow-[inset_0_0_0_2px_var(--gg-primario)]' : 'opacity-0'"
                aria-hidden="true"
            ></span>

            <div class="flex flex-col items-center justify-center gap-0.5 py-3 px-1 text-center min-h-[3.5rem]">
                <span
                    class="text-md font-medium leading-none transition-colors duration-100"
                    :class="seleccionado === {{ $opcion['valor'] }} ? 'text-gg-primario' : 'text-gg-tinta'"
                >{{ $opcion['etiqueta'] }}</span>
                <span class="text-2xs text-gg-tinta-suave leading-tight mt-0.5">
                    {{ $opcion['desc'] }}
                </span>
            </div>
        </label>
        @endforeach

    </div>

    @if($error)
    <p class="mt-1.5 text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
