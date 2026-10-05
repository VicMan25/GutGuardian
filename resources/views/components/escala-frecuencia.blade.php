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
    <p id="{{ $uid }}-label" class="text-base font-medium text-gg-tinta leading-snug mb-3">
        @if($codigo)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>
    @endif

    {{-- Contenedor segmentado: 2×2 en móvil, 4 columnas desde sm --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 p-1.5 rounded-[14px] bg-gg-papel border border-gg-borde"
         role="presentation">

        @foreach($opciones as $i => $opcion)
        <label
            class="relative cursor-pointer select-none rounded-control transition-[background-color,box-shadow,transform] duration-150 active:scale-[0.98]"
            :class="seleccionado === {{ $opcion['valor'] }}
                ? 'gg-seg-activo'
                : 'bg-gg-superficie hover:shadow-elev-1'"
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

            {{-- Indicador de foco de teclado --}}
            <span
                class="pointer-events-none absolute inset-0 rounded-control transition-opacity duration-100"
                :class="enfocado === {{ $opcion['valor'] }} ? 'outline outline-2 outline-offset-2 outline-gg-primario' : 'opacity-0'"
                aria-hidden="true"
            ></span>

            {{-- Contenido visible del segmento --}}
            <div class="flex flex-col items-center justify-center gap-0.5 py-2.5 px-2 text-center min-h-[3.75rem]">
                <span
                    class="text-sm leading-tight transition-colors duration-100"
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
    <p class="mt-2 text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
