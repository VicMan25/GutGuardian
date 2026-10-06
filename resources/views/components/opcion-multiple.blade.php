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
      destacada     bool     Enunciado grande (encuesta guiada); el código lo muestra el paso
--}}
@props([
    'nombre',
    'codigo'       => '',
    'pregunta'     => '',
    'opciones'     => [],
    'seleccionados'=> [],
    'requerido'    => false,
    'error'        => null,
    'destacada'    => false,
])

@php
$uid       = 'om-' . Str::random(8);
$idNinguna = collect($opciones)->firstWhere('es_ninguna', true)['id'] ?? null;
@endphp

<div
    x-data="opcionMultiple({{ json_encode(array_values((array)$seleccionados)) }}, {{ $idNinguna ?? 'null' }})"
    role="group"
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

    {{-- Hint: selección múltiple --}}
    <p class="text-sm text-gg-tinta-suave -mt-1 mb-3" id="{{ $uid }}-hint">
        Puede seleccionar varias opciones.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" aria-describedby="{{ $uid }}-hint">
        @foreach($opciones as $opcion)

        @php $esNinguna = $opcion['es_ninguna'] ?? false; @endphp

        <label
            class="flex items-start gap-3 min-h-[48px] px-3.5 py-3 rounded-control border cursor-pointer select-none
                   transition-[background-color,border-color,box-shadow,transform] duration-150 active:scale-[0.99] group
                   {{ $esNinguna ? 'sm:col-span-2 mt-1' : '' }}"
            :class="marcado({{ $opcion['id'] }})
                ? 'border-gg-primario bg-gg-primario-suave shadow-[inset_0_0_0_1px_var(--gg-primario)]'
                : 'border-gg-borde bg-gg-superficie hover:border-[#C7D0C9] hover:bg-gg-papel'"
        >
            {{--
                Checkbox real. Sin atributo "required": a diferencia de los radios
                (donde el navegador exige que UNO del grupo esté marcado), en un
                grupo de checkboxes "required" exige que TODOS lo estén — con esto
                puesto en cada opción, el navegador bloqueaba el envío del
                formulario sin importar cuántas opciones válidas se marcaran. La
                regla "al menos una" ya la exige el backend (ver reglasSeccion).
            --}}
            <input
                type="checkbox"
                name="{{ $nombre }}[]"
                value="{{ $opcion['id'] }}"
                class="sr-only"
                :checked="marcado({{ $opcion['id'] }})"
                @change="toggle({{ $opcion['id'] }})"
            />

            {{-- Casilla visual --}}
            <span
                class="mt-px w-5 h-5 shrink-0 rounded-[6px] border-2 flex items-center justify-center transition-colors duration-100"
                :class="marcado({{ $opcion['id'] }})
                    ? 'bg-gg-primario border-gg-primario'
                    : 'border-gg-borde bg-gg-superficie group-hover:border-gg-primario'"
                aria-hidden="true"
            >
                <svg
                    class="w-3 h-3 text-white transition-opacity duration-100"
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
    <p class="mt-2 text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
