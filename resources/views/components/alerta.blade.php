{{--
    Alerta del sistema — para mensajes de estado en la interfaz.
    Usa los colores del sistema de riesgo, nunca rojo de emergencia.

    Props:
      tipo    string   'exito' | 'error' | 'aviso' | 'info'
      titulo  string   Título opcional en negrita
      cierre  bool     Muestra botón de cierre con Alpine.js
--}}
@props([
    'tipo'   => 'info',
    'titulo' => null,
    'cierre' => false,
])

@php
$config = [
    'exito' => [
        'bg'     => 'bg-[#EBF5F0]',
        'borde'  => 'border-gg-riesgo-bajo',
        'tinta'  => 'text-gg-riesgo-bajo',
        'icono'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'label'  => 'Éxito',
    ],
    'error' => [
        'bg'     => 'bg-[#F9EDE9]',
        'borde'  => 'border-gg-riesgo-alto',
        'tinta'  => 'text-gg-riesgo-alto',
        'icono'  => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'label'  => 'Error',
    ],
    'aviso' => [
        'bg'     => 'bg-[#FBF4E4]',
        'borde'  => 'border-gg-riesgo-medio',
        'tinta'  => 'text-gg-riesgo-medio',
        'icono'  => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'label'  => 'Aviso',
    ],
    'info' => [
        'bg'     => 'bg-gg-primario-suave',
        'borde'  => 'border-gg-primario',
        'tinta'  => 'text-gg-primario',
        'icono'  => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'label'  => 'Información',
    ],
];

$c = $config[$tipo] ?? $config['info'];
@endphp

<div
    role="alert"
    aria-live="polite"
    @if($cierre)
    x-data="{ visible: true }"
    x-show="visible"
    x-transition:leave="transition-opacity duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @endif
    class="flex gap-3 p-4 rounded-control border {{ $c['bg'] }} {{ $c['borde'] }} border-l-4"
>
    {{-- Ícono --}}
    <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $c['tinta'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icono'] }}"/>
    </svg>

    {{-- Contenido --}}
    <div class="flex-1 min-w-0">
        @if($titulo)
        <p class="text-sm font-medium {{ $c['tinta'] }} mb-0.5">{{ $titulo }}</p>
        @endif
        <div class="text-sm text-gg-tinta leading-snug">
            {{ $slot }}
        </div>
    </div>

    {{-- Botón de cierre --}}
    @if($cierre)
    <button
        type="button"
        @click="visible = false"
        class="shrink-0 p-0.5 rounded {{ $c['tinta'] }} hover:opacity-70 transition-opacity"
        aria-label="Cerrar aviso"
    >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    @endif

</div>
