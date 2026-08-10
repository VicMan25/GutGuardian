{{--
    Matriz de ítems × escala — para P11, P12, P16, P17.
    Móvil (<640 px): acordeón — un ítem por vez, selector de escala debajo.
    Desktop (≥640 px): tabla clásica con columnas de opciones.

    Props:
      nombre    string   Prefijo para los name de los inputs: nombre[item_id]
      codigo    string   Código de la pregunta (P11, P12...)
      pregunta  string   Enunciado de la pregunta
      items     array    [['id' => int, 'etiqueta' => string], ...]
      opciones  array    [['valor' => int, 'etiqueta' => string, 'equiv' => string], ...]
      respuestas array   [item_id => valor, ...] — valores ya guardados (opcional)
      requerido bool
      error     string
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
])

@php
$uid        = 'mx-' . Str::random(8);
$totalItems = count($items);
@endphp

<div x-data="matrizSintomas({{ $totalItems }})" class="w-full">

    {{-- Encabezado de pregunta --}}
    @if($pregunta)
    <p id="{{ $uid }}-label" class="text-sm font-medium text-gg-tinta leading-snug mb-4">
        @if($codigo)
            <span class="font-mono text-2xs text-gg-tinta-suave mr-1.5 select-none">{{ $codigo }}</span>
        @endif
        {{ $pregunta }}
        @if($requerido)
            <span class="text-gg-riesgo-medio ml-0.5" aria-hidden="true">*</span>
        @endif
    </p>
    @endif

    {{-- Progreso: N de M ítems respondidos (accesible) --}}
    <div class="flex items-center justify-between mb-3">
        <span class="text-2xs text-gg-tinta-suave" aria-live="polite" aria-atomic="true">
            <span x-text="totalRespondidos()">0</span> de {{ $totalItems }} respondidos
        </span>
        <span
            class="text-2xs font-medium text-gg-primario transition-opacity duration-200"
            x-show="todosRespondidos()"
        >Completado</span>
    </div>

    {{-- ============================================================
         VISTA MÓVIL — acordeón (bloque sm:hidden)
    ============================================================ --}}
    <div class="sm:hidden space-y-1.5" role="list" aria-label="{{ $pregunta }}">

        @foreach($items as $idx => $item)
        <div role="listitem" class="border border-gg-borde rounded-control overflow-hidden">

            {{-- Cabecera del ítem --}}
            <button
                type="button"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left
                       transition-colors duration-100
                       hover:bg-gg-papel focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-gg-primario"
                :class="expandido === {{ $idx }} ? 'bg-gg-primario-suave' : 'bg-gg-superficie'"
                @click="expandir({{ $idx }})"
                :aria-expanded="expandido === {{ $idx }}"
                aria-controls="{{ $uid }}-item-{{ $idx }}"
            >
                <span
                    class="text-sm leading-snug transition-colors duration-100"
                    :class="expandido === {{ $idx }} ? 'text-gg-primario font-medium' : 'text-gg-tinta'"
                >{{ $item['etiqueta'] }}</span>

                <span class="shrink-0 flex items-center gap-2">
                    {{-- Tick si ya respondido --}}
                    <span
                        x-show="respondido({{ $idx }})"
                        class="text-gg-primario"
                        aria-label="Respondido"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    {{-- Chevron --}}
                    <svg class="w-4 h-4 text-gg-tinta-suave transition-transform duration-200"
                         :class="expandido === {{ $idx }} ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </button>

            {{-- Panel de opciones --}}
            <div
                id="{{ $uid }}-item-{{ $idx }}"
                x-show="expandido === {{ $idx }}"
                x-transition:enter="transition-all duration-150 ease-out"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all duration-100 ease-in"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="border-t border-gg-borde bg-gg-superficie"
                role="radiogroup"
                :aria-label="'Frecuencia de: {{ $item['etiqueta'] }}'"
            >
                <div class="grid grid-cols-{{ count($opciones) <= 4 ? count($opciones) : '3' }} divide-x divide-gg-borde">
                    @foreach($opciones as $j => $opcion)
                    <label
                        class="relative cursor-pointer select-none transition-colors duration-100
                               hover:bg-gg-papel"
                        :class="respuestas[{{ $idx }}] === {{ $opcion['valor'] }} ? 'gg-seg-activo' : ''"
                    >
                        <input
                            type="radio"
                            name="{{ $nombre }}[{{ $item['id'] }}]"
                            value="{{ $opcion['valor'] }}"
                            class="sr-only"
                            {{ $requerido ? 'required' : '' }}
                            :checked="respuestas[{{ $idx }}] === {{ $opcion['valor'] }}"
                            @change="responder({{ $idx }}, {{ $opcion['valor'] }})"
                        />
                        <div class="flex flex-col items-center justify-center gap-0.5 py-3 px-1 text-center min-h-[3rem]">
                            <span
                                class="text-xs leading-tight transition-colors duration-100"
                                :class="respuestas[{{ $idx }}] === {{ $opcion['valor'] }} ? 'text-gg-primario font-medium' : 'text-gg-tinta'"
                            >{{ $opcion['etiqueta'] }}</span>
                            @if(isset($opcion['equiv']))
                            <span class="text-2xs text-gg-tinta-suave font-mono">{{ $opcion['equiv'] }}</span>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

        </div>
        @endforeach

    </div>

    {{-- ============================================================
         VISTA DESKTOP — tabla (bloque hidden sm:block)
    ============================================================ --}}
    <div class="hidden sm:block overflow-x-auto -mx-1">
        <table class="w-full min-w-max text-left border-collapse" role="table" aria-label="{{ $pregunta }}">
            <thead>
                <tr>
                    <th class="py-2 pr-4 w-48 text-xs font-medium text-gg-tinta-suave border-b border-gg-borde" scope="col">
                        Síntoma / Factor
                    </th>
                    @foreach($opciones as $opcion)
                    <th class="py-2 px-3 text-center text-xs font-medium text-gg-tinta-suave border-b border-gg-borde whitespace-nowrap" scope="col">
                        {{ $opcion['etiqueta'] }}
                        @if(isset($opcion['equiv']))
                        <br><span class="font-mono font-normal text-2xs">{{ $opcion['equiv'] }}</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                <tr
                    class="border-b border-gg-borde last:border-b-0 transition-colors duration-75"
                    :class="{ 'bg-gg-papel': {{ $idx }} % 2 === 1 }"
                    role="radiogroup"
                    :aria-label="'{{ $item['etiqueta'] }}'"
                >
                    <td class="py-2.5 pr-4 text-sm text-gg-tinta leading-snug">
                        {{ $item['etiqueta'] }}
                    </td>
                    @foreach($opciones as $opcion)
                    <td class="py-2.5 px-3 text-center">
                        <label class="inline-flex items-center justify-center cursor-pointer group">
                            <input
                                type="radio"
                                name="{{ $nombre }}[{{ $item['id'] }}]"
                                value="{{ $opcion['valor'] }}"
                                class="sr-only peer"
                                {{ $requerido ? 'required' : '' }}
                                :checked="respuestas[{{ $idx }}] === {{ $opcion['valor'] }}"
                                @change="respuestas[{{ $idx }}] = {{ $opcion['valor'] }}"
                                @focus="expandido = {{ $idx }}"
                            />
                            {{-- Círculo visual de radio --}}
                            <span class="w-5 h-5 rounded-full border-2 border-gg-borde flex items-center justify-center
                                         group-hover:border-gg-primario transition-colors duration-100
                                         peer-checked:border-gg-primario peer-checked:bg-gg-primario
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-gg-primario peer-focus-visible:ring-offset-1"
                                  aria-hidden="true">
                                <span class="w-2 h-2 rounded-full bg-gg-superficie opacity-0 peer-checked:opacity-100 transition-opacity"
                                      x-show="respuestas[{{ $idx }}] === {{ $opcion['valor'] }}"></span>
                            </span>
                        </label>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($error)
    <p class="mt-2 text-2xs text-gg-riesgo-alto" role="alert">{{ $error }}</p>
    @endif

</div>
