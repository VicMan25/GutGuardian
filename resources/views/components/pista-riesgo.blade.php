{{--
    Pista de riesgo — muestra la clasificación multinomial del estudiante.
    Tres segmentos (bajo/medio/alto) con marcador en la categoría asignada.
    La misma gramática visual que escala-frecuencia conecta "cómo respondiste"
    con "dónde quedaste".

    IMPORTANTE: siempre incluye aviso-no-diagnostico debajo del resultado.
    Obligatorio por Resolución 3100 de 2019.

    Props:
      categoria       int     0=bajo, 1=medio, 2=alto
      probBajo        float   Probabilidad categoría 0 (0-1)
      probMedio       float   Probabilidad categoría 1 (0-1)
      probAlto        float   Probabilidad categoría 2 (0-1)
      contribuciones  array   [['etiqueta' => string, 'odds' => float], ...]  (top 3-5)
      fecha           string  Fecha del diligenciamiento
      codigoParticipante string|null
--}}
@props([
    'categoria',
    'probBajo'            => 0.0,
    'probMedio'           => 0.0,
    'probAlto'            => 0.0,
    'contribuciones'      => [],
    'fecha'               => '',
    'codigoParticipante'  => null,
])

@php
$etiquetas = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
$colores   = ['gg-riesgo-bajo', 'gg-riesgo-medio', 'gg-riesgo-alto'];
$bgHex     = ['#3E7D64', '#C08A2E', '#9C4A32'];
$bgSuave   = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];

$etiquetaActual = $etiquetas[$categoria];
$colorActual    = $colores[$categoria];
$bgSuaveActual  = $bgSuave[$categoria];

$probabilidades = [
    round($probBajo  * 100, 1),
    round($probMedio * 100, 1),
    round($probAlto  * 100, 1),
];
@endphp

<section aria-labelledby="resultado-titulo" class="space-y-5">

    {{-- Encabezado --}}
    <div>
        <p class="text-xs text-gg-tinta-suave mb-0.5">
            Tu clasificación de riesgo
            @if($fecha)
            · <time datetime="{{ $fecha }}">{{ $fecha }}</time>
            @endif
        </p>
        @if($codigoParticipante)
        <p class="font-mono text-2xs text-gg-tinta-suave mb-1">
            Código: {{ $codigoParticipante }}
        </p>
        @endif
        <h2 id="resultado-titulo"
            class="font-display text-2xl font-medium"
            style="color: {{ $bgHex[$categoria] }}">
            {{ $etiquetaActual }}
        </h2>
    </div>

    {{-- Pista de tres segmentos --}}
    <div class="relative" role="img" :aria-label="'Resultado: {{ $etiquetaActual }}'">
        <div class="grid grid-cols-3 border border-gg-borde rounded-control overflow-hidden"
             aria-hidden="true">
            @foreach($etiquetas as $i => $etiqueta)
            <div
                class="relative py-4 px-2 flex flex-col items-center justify-center gap-1 text-center
                       {{ $i < 2 ? 'border-r border-gg-borde' : '' }}"
                style="background-color: {{ $categoria === $i ? $bgSuave[$i] : 'var(--gg-superficie)' }}"
            >
                {{-- Marcador de posición actual --}}
                @if($categoria === $i)
                <span class="absolute -top-0 left-1/2 -translate-x-1/2 -translate-y-full"
                      aria-hidden="true">
                    <svg width="10" height="7" viewBox="0 0 10 7" fill="{{ $bgHex[$i] }}">
                        <polygon points="5,7 0,0 10,0"/>
                    </svg>
                </span>
                @endif

                <span
                    class="text-xs font-medium leading-tight"
                    style="color: {{ $categoria === $i ? $bgHex[$i] : 'var(--gg-tinta-suave)' }}"
                >{{ $etiqueta }}</span>

                <span
                    class="font-mono text-2xs"
                    style="color: {{ $categoria === $i ? $bgHex[$i] : 'var(--gg-tinta-suave)' }}"
                >{{ $probabilidades[$i] }}&thinsp;%</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Contribuciones principales --}}
    @if(count($contribuciones) > 0)
    <div>
        <p class="text-xs font-medium text-gg-tinta mb-2">
            Factores con mayor contribución al resultado:
        </p>
        <ul class="space-y-1.5" role="list">
            @foreach($contribuciones as $c)
            <li class="flex items-baseline justify-between gap-4 text-sm">
                <span class="text-gg-tinta leading-snug">{{ $c['etiqueta'] }}</span>
                <span class="font-mono text-xs text-gg-tinta-suave whitespace-nowrap shrink-0">
                    ×{{ number_format($c['odds'], 1) }}
                </span>
            </li>
            @endforeach
        </ul>
        <p class="mt-2 text-2xs text-gg-tinta-suave">
            Los valores ×N son odds ratios: indican cuántas veces aumenta la probabilidad relativa de esta categoría respecto a riesgo bajo.
        </p>
    </div>
    @endif

    {{-- Aviso de no-diagnóstico — obligatorio por Res. 3100/2019 --}}
    <x-aviso-no-diagnostico />

</section>
