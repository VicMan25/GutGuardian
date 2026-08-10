{{--
    Selección múltiple con lógica "Ninguna" excluyente — para P14, P15, P18, P20.
    Cada opción genera un indicador binario independiente para el modelo.
    "Ninguna" deselecciona todas las demás; cualquier otra opción deselecciona "Ninguna".

    Props:
      nombre        string   Prefijo: name="{{ $nombre }}[]", value="opcion_id"
      codigo        string   Código de pregunta
      pregunta      string   Enunciado
      opciones      array    [['id' => int, 'etiqueta' => string, 'es_ninguna' => bool], ...]
      seleccionados array    IDs de opciones ya seleccionadas
      requerido     bool
      error         string
--}}
@props([
    'nombre',
    'codigo'       => '',
    'pregunta'     => '',
    'opciones'     => [],
    'seleccionados'=> [],
    'requerido'    => false,
    'error'        => null,
])

@php
$uid       = 'om-' . Str::random(8);
$idNinguna = collect($opciones)->firstWhere('es_ninguna', true)['id'] ?? null;
@endphp

<div
    x-data="opcionMultiple({{ json_encode(array_values((array)$seleccionados)) }}, {{ $idNinguna ?? 'null' }})"
    role="group"
    aria-labelledby="{{ $uid }}-label"
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

    {{-- Hint: selección múltiple --}}
    <p class="text-2xs text-gg-tinta-suave mb-3" id="{{ $uid }}-hint">
        Puede seleccionar varias opciones.
    </p>

    <div class="space-y-2" aria-describedby="{{ $uid }}-hint">
        @foreach($opciones as $opcion)

        @php $esNinguna = $opcion['es_ninguna'] ?? false; @endphp

        <label
            class="flex items-start gap-3 p-3 rounded-control border cursor-pointer select-none
                   transition-colors duration-100 group"
            :class="marcado({{ $opcion['id'] }})
                ? 'border-gg-primario bg-gg-primario-suave'
                : 'border-gg-borde bg-gg-superficie hover:bg-gg-papel'"
        >
            {{-- Checkbox real --}}
            <input
                type="checkbox"
                name="{{ $nombre }}[]"
                value="{{ $opcion['id'] }}"
                class="sr-only"
                {{ $requerido ? 'required' : '' }}
                :checked="marcado({{ $opcion['id'] }})"
                @change="toggle({{ $opcion['id'] }})"
            />

            {{-- Casilla visual --}}
            <span
                class="mt-0.5 w-4 h-4 shrink-0 rounded-[4px] border-2 flex items-center justify-center transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }})
                    ? 'bg-gg-primario border-gg-primario'
                    : 'border-gg-borde bg-gg-superficie group-hover:border-gg-primario'"
                aria-hidden="true"
            >
                <svg
                    class="w-2.5 h-2.5 text-white transition-opacity duration-100"
                    :class="marcado({{ $opcion['id'] }}) ? 'opacity-100' : 'opacity-0'"
                    viewBox="0 0 12 12" fill="none"
                >
                    <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>

            {{-- Etiqueta --}}
            <span
                class="text-sm leading-snug transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }})
                    ? 'text-gg-primario font-medium'
                    : '{{ $esNinguna ? 'text-gg-tinta-suave italic' : 'text-gg-tinta' }}'"
            >{{ $opcion['etiqueta'] }}</span>
        </label>

        @endforeach
    </div>

    @if($error)
    <p class="mt-2 text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
