{{--
    Botón del sistema de diseño GutGuardián.
    Variantes: primario (acción principal), secundario (acción secundaria),
    fantasma (acción terciaria / destructiva suave), claro (sobre superficies
    de marca verdes).

    Al enviar un formulario POST, app.js le aplica [data-cargando]: el texto
    se oculta y aparece un indicador giratorio hasta la respuesta.

    Props:
      variante     string   'primario' | 'secundario' | 'fantasma' | 'claro'
      tipo         string   'button' | 'submit' | 'reset'
      href         string   Si se provee, renderiza como <a>
      discapacitado bool    Deshabilita el control
      tamano       string   'sm' | 'md' (por defecto) | 'lg'
      icono        string   Ícono (x-icono) antes del texto
      iconoFinal   string   Ícono después del texto (p. ej. 'flecha')
--}}
@props([
    'variante'     => 'primario',
    'tipo'         => 'button',
    'href'         => null,
    'discapacitado'=> false,
    'tamano'       => 'md',
    'icono'        => null,
    'iconoFinal'   => null,
])

@php
$base = 'group/boton inline-flex items-center justify-center gap-2 font-medium rounded-control whitespace-nowrap
         border select-none transition-[background-color,border-color,color,box-shadow,transform] duration-150 ease-out
         active:translate-y-px
         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gg-primario focus-visible:ring-offset-2
         disabled:opacity-50 disabled:cursor-not-allowed disabled:active:translate-y-0
         aria-disabled:opacity-50 aria-disabled:pointer-events-none';

$tamanos = [
    'sm' => 'min-h-[38px] px-3.5 text-sm',
    'md' => 'min-h-[44px] px-5 text-base',
    'lg' => 'min-h-[52px] px-6 text-base',
];

$variantes = [
    'primario'   => 'bg-gg-primario text-white border-transparent shadow-boton hover:bg-gg-primario-hondo active:bg-gg-primario-noche',
    'secundario' => 'gg-boton-claro bg-gg-superficie text-gg-primario border-gg-borde shadow-elev-1 hover:border-[#B9CBBF] hover:bg-gg-primario-suave active:bg-[#d3e7db]',
    'fantasma'   => 'gg-boton-claro bg-transparent text-gg-tinta-suave border-transparent hover:text-gg-tinta hover:bg-gg-papel-hondo active:bg-gg-borde',
    'claro'      => 'gg-boton-claro bg-white text-gg-primario-hondo border-transparent shadow-elev-2 hover:bg-gg-acento focus-visible:ring-white focus-visible:ring-offset-gg-primario-hondo',
];

$clases = trim("$base {$tamanos[$tamano]} {$variantes[$variante]}");
$tamanoIcono = $tamano === 'sm' ? 'w-4 h-4' : 'w-[18px] h-[18px]';
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $clases]) }}
        @if($discapacitado) aria-disabled="true" tabindex="-1" @endif
    >
        @if($icono)<x-icono :nombre="$icono" :class="$tamanoIcono" />@endif
        {{ $slot }}
        @if($iconoFinal)<x-icono :nombre="$iconoFinal" :class="$tamanoIcono.' transition-transform duration-200 group-hover/boton:translate-x-0.5'" />@endif
    </a>
@else
    <button
        type="{{ $tipo }}"
        {{ $discapacitado ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => $clases]) }}
    >
        @if($icono)<x-icono :nombre="$icono" :class="$tamanoIcono" />@endif
        {{ $slot }}
        @if($iconoFinal)<x-icono :nombre="$iconoFinal" :class="$tamanoIcono.' transition-transform duration-200 group-hover/boton:translate-x-0.5'" />@endif
    </button>
@endif
