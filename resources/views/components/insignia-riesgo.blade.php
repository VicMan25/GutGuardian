{{--
    Insignia de nivel de riesgo — misma apariencia en historial, listados,
    ficha y reportes. Punto de color + texto: el color nunca es el único
    portador del significado (WCAG 1.4.1).

    Props:
      categoria  int|null   0=bajo, 1=medio, 2=alto. null → "Sin evaluar"
      tamano     string     'sm' | 'md'
--}}
@props([
    'categoria' => null,
    'tamano'    => 'md',
])

@php
    $etiquetas = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
    // Tinta oscurecida de cada color de riesgo para cumplir contraste AA sobre su fondo suave.
    $tinta  = ['#2F664F', '#8A6116', '#86402B'];
    $punto  = ['#3E7D64', '#C08A2E', '#9C4A32'];
    $fondo  = ['#E8F3EE', '#FBF3E2', '#F7ECE8'];
    $borde  = ['#CFE4D9', '#F0DFBA', '#EBD3CB'];

    $clasesTamano = $tamano === 'sm' ? 'text-xs px-2 py-0.5 gap-1.5' : 'text-sm px-2.5 py-1 gap-2';
@endphp

@if($categoria === null)
    <span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border border-gg-borde bg-gg-papel text-gg-tinta-suave whitespace-nowrap $clasesTamano"]) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-gg-borde" aria-hidden="true"></span>
        Sin evaluar
    </span>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border font-medium whitespace-nowrap $clasesTamano"]) }}
          style="color: {{ $tinta[$categoria] }}; background-color: {{ $fondo[$categoria] }}; border-color: {{ $borde[$categoria] }};">
        <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $punto[$categoria] }}" aria-hidden="true"></span>
        {{ $etiquetas[$categoria] }}
    </span>
@endif
