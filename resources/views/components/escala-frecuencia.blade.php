{{--
    Selector segmentado de frecuencia — elemento de firma de GutGuardián.
    Usado en P01-P07, P10, P19 (escala simple) y como componente de ópciones
    en P09, P12, P17 (dentro de matriz-sintomas).

    Props:
      nombre     string   Atributo name del input (obligatorio)
      codigo     string   Código de pregunta: P01, P09, etc.
      pregunta   string   Enunciado de la pregunta
      valor      int|null Valor seleccionado actualmente (0-3), null si sin respuesta
      requerido  bool     Agrega el atributo required a los inputs
      error      string   Mensaje de error a mostrar debajo del selector
--}}
@props([
    'nombre',
    'codigo'   => '',
    'pregunta' => '',
    'valor'    => null,
    'requerido'=> false,
    'error'    => null,
])

@php
$uid = 'ef-' . Str::random(8);

$opciones = [
    ['valor' => 0, 'etiqueta' => 'Nunca',          'equiv' => '—'],
    ['valor' => 1, 'etiqueta' => 'Ocasionalmente',  'equiv' => '1+/mes'],
    ['valor' => 2, 'etiqueta' => 'Algunas veces',   'equiv' => '1–6/sem'],
    ['valor' => 3, 'etiqueta' => 'Siempre',         'equiv' => 'diario'],
];
@endphp

<div
    x-data="escalaFrecuencia({{ $valor ?? 'null' }})"
    role="radiogroup"
    aria-labelledby="{{ $uid }}-label"
    aria-required="{{ $requerido ? 'true' : 'false' }}"
    @keydown.arrow-right.prevent="siguiente()"
    @keydown.arrow-left.prevent="anterior()"
    @keydown.arrow-down.prevent="siguiente()"
    @keydown.arrow-up.prevent="anterior()"
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

    {{-- Contenedor segmentado --}}
    <div class="grid grid-cols-4 border border-gg-borde rounded-control overflow-hidden"
         role="presentation">

        @foreach($opciones as $i => $opcion)
        <label
            class="relative cursor-pointer select-none transition-colors duration-100"
            :class="{
                'gg-seg-activo': seleccionado === {{ $opcion['valor'] }},
                'hover:bg-gg-papel': seleccionado !== {{ $opcion['valor'] }},
            }"
        >
            {{-- Input real — accesible a teclado y lector de pantalla --}}
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

            {{-- Separador vertical entre segmentos (excepto el último) --}}
            @if($i < 3)
            <span
                class="absolute right-0 top-0 h-full w-px bg-gg-borde"
                :class="(seleccionado === {{ $opcion['valor'] }} || seleccionado === {{ $opciones[$i+1]['valor'] }}) ? 'opacity-0' : 'opacity-100'"
                aria-hidden="true"
            ></span>
            @endif

            {{-- Indicador de foco de teclado --}}
            <span
                class="pointer-events-none absolute inset-0 rounded-sm transition-opacity duration-100"
                :class="enfocado === {{ $opcion['valor'] }} ? 'shadow-[inset_0_0_0_2px_var(--gg-primario)]' : 'opacity-0'"
                aria-hidden="true"
            ></span>

            {{-- Contenido visible del segmento --}}
            <div class="flex flex-col items-center justify-center gap-0.5 py-3 px-1 text-center min-h-[3.5rem]">
                <span
                    class="text-xs leading-tight transition-colors duration-100"
                    :class="seleccionado === {{ $opcion['valor'] }} ? 'text-gg-primario font-medium' : 'text-gg-tinta'"
                >{{ $opcion['etiqueta'] }}</span>
                <span class="text-2xs text-gg-tinta-suave leading-tight font-mono">
                    {{ $opcion['equiv'] }}
                </span>
            </div>
        </label>
        @endforeach

    </div>

    @if($error)
    <p class="mt-1.5 text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
