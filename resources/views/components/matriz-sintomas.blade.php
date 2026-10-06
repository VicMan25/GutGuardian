{{--
    Matriz de ítems × escala — para P09, P11, P12, P16, P17.

    Un ítem (síntoma, medicamento, sustancia) a la vez, en todos los tamaños
    de pantalla: el enunciado y sus opciones siempre caben juntos, sin la
    tabla de 7 columnas que obligaba a desplazarse en horizontal. Arriba,
    chips con todos los ítems muestran el avance y permiten saltar a
    cualquiera; al responder el último aparece un resumen editable.

    Una sola copia de cada input (name="nombre[item_id]"): los ítems que no
    están a la vista siguen en el DOM y se envían con el formulario.

    Props:
      nombre    string   Prefijo para los name de los inputs: nombre[item_id]
      codigo    string   Código de la pregunta (P11, P12...)
      pregunta  string   Enunciado de la pregunta
      items     array    [['id' => int, 'etiqueta' => string], ...]
      opciones  array    [['valor' => int, 'etiqueta' => string, 'equiv' => string], ...]
      respuestas array   [item_id => valor, ...] — valores ya guardados (opcional)
      requerido bool
      error     string
      destacada bool     Enunciado grande (encuesta guiada); el código lo muestra el paso
--}}
@props([
    'nombre',
    'codigo'    => '',
    'pregunta'  => '',
    'items'     => [],
    'opciones'  => [],
    'respuestas'=> [],
    'requerido' => false,
    'error'     => null,
    'destacada' => false,
])

@php
$uid        = 'mx-' . Str::random(8);
$totalItems = count($items);

// El estado de Alpine indexa las respuestas por posición dentro de $items (idx),
// pero el prop $respuestas llega indexado por item_pregunta_id (para poder
// prellenar al reanudar un diligenciamiento). Se traduce aquí una sola vez.
$respuestasPorIndice = [];
foreach ($items as $idx => $item) {
    if (array_key_exists($item['id'], $respuestas)) {
        $respuestasPorIndice[$idx] = $respuestas[$item['id']];
    }
}

// Ítem que se abre al cargar (lo mismo que calcula matrizSintomas.init),
// para que el HTML del servidor ya muestre el panel correcto.
$itemInicial = collect(array_keys($items))->first(fn ($i) => ! array_key_exists($i, $respuestasPorIndice));

// Clases literales (no interpoladas) para que Tailwind no las purgue.
$columnasOpciones = match (true) {
    count($opciones) <= 4 => 'grid-cols-2 sm:grid-cols-4',
    default               => 'grid-cols-2 sm:grid-cols-3',
};
@endphp

<div x-data="matrizSintomas({{ $totalItems }}, {{ json_encode($respuestasPorIndice) }})" class="w-full">

    {{-- Encabezado de pregunta --}}
    @if($pregunta)
    <p id="{{ $uid }}-label"
       class="{{ $destacada ? 'font-display text-xl sm:text-2xl font-medium text-gg-tinta leading-snug mb-4' : 'text-base font-medium text-gg-tinta leading-snug mb-4' }}">
        @if($codigo && ! $destacada)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>
    @endif

    {{-- Progreso: N de M ítems respondidos (accesible) --}}
    <div class="flex items-center justify-between gap-3 mb-3">
        <span class="text-sm text-gg-tinta-suave" aria-live="polite" aria-atomic="true">
            <span x-text="totalRespondidos()">{{ count($respuestasPorIndice) }}</span> de {{ $totalItems }} respondidos
        </span>
        <span
            class="inline-flex items-center gap-1 text-sm font-medium text-gg-primario"
            x-show="todosRespondidos()"
            x-transition.opacity
        ><x-icono nombre="check" class="w-4 h-4" />Completado</span>
    </div>

    {{-- Chips: todos los ítems a la vista, con su estado; tocar uno lo abre --}}
    <div class="flex flex-wrap gap-1.5 mb-4" aria-label="Ítems de la pregunta">
        @foreach($items as $idx => $item)
        <button
            type="button"
            @click="expandido = {{ $idx }}"
            :aria-pressed="(expandido === {{ $idx }}).toString()"
            class="inline-flex items-center gap-1.5 min-h-[36px] px-3 rounded-full border text-sm transition-[background-color,border-color,color] duration-150"
            :class="expandido === {{ $idx }}
                ? 'bg-gg-primario border-gg-primario text-white'
                : (respondido({{ $idx }})
                    ? 'bg-gg-primario-suave border-[#CFE0D6] text-gg-primario'
                    : 'bg-gg-superficie border-gg-borde text-gg-tinta-suave hover:border-[#C7D0C9] hover:text-gg-tinta')"
        >
            <x-icono nombre="check" class="w-3.5 h-3.5" x-show="respondido({{ $idx }})" />
            {{ $item['etiqueta'] }}
            <span class="sr-only" x-text="respondido({{ $idx }}) ? '(respondido)' : '(sin responder)'"></span>
        </button>
        @endforeach
    </div>

    {{-- Un panel por ítem; solo el abierto es visible --}}
    @foreach($items as $idx => $item)
    <div
        x-show="expandido === {{ $idx }}"
        @if($idx !== $itemInicial) style="display: none" @endif
        class="gg-item-entra rounded-tarjeta bg-gg-papel border border-gg-borde p-3 sm:p-4"
        role="radiogroup"
        aria-labelledby="{{ $uid }}-item-{{ $idx }}"
    >
        <p id="{{ $uid }}-item-{{ $idx }}" class="flex items-baseline justify-between gap-3 px-1 mb-3">
            <span class="text-md font-medium text-gg-tinta">{{ $item['etiqueta'] }}</span>
            <span class="font-mono text-xs text-gg-tinta-suave shrink-0">{{ $idx + 1 }}/{{ $totalItems }}</span>
        </p>

        <div class="grid {{ $columnasOpciones }} gap-2">
            @foreach($opciones as $opcion)
            <label
                class="relative cursor-pointer select-none rounded-control transition-[background-color,box-shadow,transform] duration-150 active:scale-[0.98]"
                :class="respuestas[{{ $idx }}] === {{ $opcion['valor'] }} ? 'gg-seg-activo' : 'bg-gg-superficie border border-gg-borde hover:shadow-elev-1'"
            >
                <input
                    type="radio"
                    name="{{ $nombre }}[{{ $item['id'] }}]"
                    value="{{ $opcion['valor'] }}"
                    class="peer sr-only"
                    :checked="respuestas[{{ $idx }}] === {{ $opcion['valor'] }}"
                    @change="responder({{ $idx }}, {{ $opcion['valor'] }})"
                />
                <span class="pointer-events-none absolute inset-0 rounded-control peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-gg-primario" aria-hidden="true"></span>
                <span class="flex flex-col items-center justify-center gap-0.5 px-2 py-2.5 text-center min-h-[3.5rem]">
                    <span
                        class="text-sm leading-tight transition-colors duration-100"
                        :class="respuestas[{{ $idx }}] === {{ $opcion['valor'] }} ? 'text-gg-primario font-medium' : 'text-gg-tinta'"
                    >{{ $opcion['etiqueta'] }}</span>
                    @if(isset($opcion['equiv']))
                    <span class="text-2xs text-gg-tinta-suave font-mono">{{ $opcion['equiv'] }}</span>
                    @endif
                </span>
            </label>
            @endforeach
        </div>

        {{-- Control explícito: teclado y corrección de un ítem ya respondido --}}
        <div class="mt-3 flex items-center justify-between gap-2" x-show="respondido({{ $idx }})" x-cloak>
            <span class="text-xs text-gg-tinta-suave px-1">Toca otra opción para cambiarla.</span>
            <button type="button"
                    @click="avanzarItem({{ $idx }})"
                    class="inline-flex items-center gap-1 min-h-[36px] px-3 rounded-full text-sm font-medium text-gg-primario hover:bg-gg-primario-suave transition-colors">
                <span x-text="siguientePendiente({{ $idx }}) === null ? 'Ver resumen' : 'Siguiente'"></span>
                <x-icono nombre="flecha" class="w-4 h-4" />
            </button>
        </div>
    </div>
    @endforeach

    {{-- Resumen: aparece cuando ya no hay ítem abierto --}}
    <div x-show="expandido === null"
         @if($itemInicial !== null) style="display: none" @endif
         class="gg-item-entra grid sm:grid-cols-2 gap-2">
        @foreach($items as $idx => $item)
        <button type="button"
                @click="expandido = {{ $idx }}"
                class="group flex items-center justify-between gap-3 min-h-[48px] px-3.5 py-2 rounded-control border text-left transition-colors"
                :class="respondido({{ $idx }}) ? 'bg-gg-superficie border-gg-borde hover:border-[#B9CBBF]' : 'bg-[#FDF6F3] border-[#EBD3CB]'">
            <span class="text-sm text-gg-tinta">{{ $item['etiqueta'] }}</span>
            <span class="flex items-center gap-2 shrink-0">
                <span class="text-sm font-medium text-gg-primario">
                    @foreach($opciones as $opcion)
                        <span x-show="respuestas[{{ $idx }}] === {{ $opcion['valor'] }}"
                              @if(($respuestasPorIndice[$idx] ?? null) != $opcion['valor'] || ! array_key_exists($idx, $respuestasPorIndice)) style="display: none" @endif>{{ $opcion['etiqueta'] }}</span>
                    @endforeach
                    <span x-show="!respondido({{ $idx }})" class="text-gg-riesgo-alto font-normal"
                          @if(array_key_exists($idx, $respuestasPorIndice)) style="display: none" @endif>Sin responder</span>
                </span>
                <x-icono nombre="editar" class="w-4 h-4 text-gg-tinta-suave opacity-60 group-hover:opacity-100" />
            </span>
        </button>
        @endforeach
    </div>

    @if($error)
    <p class="mt-3 text-sm text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
