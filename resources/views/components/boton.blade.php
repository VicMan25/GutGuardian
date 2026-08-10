{{--
    Botón del sistema de diseño GutGuardián.
    Tres variantes: primario (acción principal), secundario (acción secundaria),
    fantasma (acción terciaria / destructiva suave).

    Props:
      variante     string   'primario' | 'secundario' | 'fantasma'
      tipo         string   'button' | 'submit' | 'reset'
      href         string   Si se provee, renderiza como <a>
      discapacitado bool    Deshabilita el control
      tamano       string   'sm' | 'md' (por defecto)
--}}
@props([
    'variante'     => 'primario',
    'tipo'         => 'button',
    'href'         => null,
    'discapacitado'=> false,
    'tamano'       => 'md',
])

@php
$base = 'inline-flex items-center justify-center gap-2 font-medium rounded-control
         border transition-colors duration-150 select-none
         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gg-primario focus-visible:ring-offset-1
         disabled:opacity-50 disabled:cursor-not-allowed';

$tamanos = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2.5 text-sm',
];

$variantes = [
    'primario'   => 'bg-gg-primario text-white border-transparent hover:bg-[#194d3e] active:bg-[#133d30]',
    'secundario' => 'bg-transparent text-gg-primario border-gg-primario hover:bg-gg-primario-suave active:bg-[#d3e7db]',
    'fantasma'   => 'bg-transparent text-gg-tinta-suave border-transparent hover:text-gg-tinta hover:bg-gg-papel active:bg-gg-borde',
];

$clases = trim("$base {$tamanos[$tamano]} {$variantes[$variante]}");
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $clases]) }}
        @if($discapacitado) aria-disabled="true" tabindex="-1" @endif
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $tipo }}"
        {{ $discapacitado ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => $clases]) }}
    >
        {{ $slot }}
    </button>
@endif
