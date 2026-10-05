{{--
    Pista de riesgo — muestra la clasificación multinomial del estudiante.
    Tres segmentos (bajo/medio/alto) con marcador en la categoría asignada.
    La misma gramática visual que escala-frecuencia conecta "cómo respondiste"
    con "dónde quedaste".

    Jerarquía: 1) nivel asignado (cifra grande, color de su categoría),
    2) pista con la probabilidad de cada nivel, 3) factores que más pesaron,
    4) aviso de no-diagnóstico.

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
$cortas    = ['Bajo', 'Medio', 'Alto'];
$bgHex     = ['#3E7D64', '#C08A2E', '#9C4A32'];
// Variante oscurecida para texto sobre fondo claro (contraste AA)
$tintaHex  = ['#2F664F', '#8A6116', '#86402B'];
$bgSuave   = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];
// Definición de cada categoría (CLAUDE.md §7). Describe el grupo, no a la persona:
// no es una interpretación clínica.
$frases    = [
    'Este nivel agrupa respuestas con ausencia o mínima presencia de síntomas gastrointestinales.',
    'Este nivel agrupa respuestas con síntomas recurrentes o una combinación de factores de riesgo nutricionales y clínicos.',
    'Este nivel agrupa respuestas con sintomatología persistente, varios antecedentes y patrones alimentarios de riesgo.',
];

$etiquetaActual = $etiquetas[$categoria];

$probabilidades = [
    round($probBajo  * 100, 1),
    round($probMedio * 100, 1),
    round($probAlto  * 100, 1),
];

$maxOdds = collect($contribuciones)->max('odds') ?: 1;
@endphp

<section aria-labelledby="resultado-titulo" class="space-y-7">

    {{-- Encabezado: el nivel asignado es lo primero que se lee --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm text-gg-tinta-suave">
                Tu clasificación de riesgo
                @if($fecha)
                · <time datetime="{{ $fecha }}">{{ $fecha }}</time>
                @endif
            </p>
            <h2 id="resultado-titulo"
                class="mt-1 font-display text-3xl sm:text-4xl font-medium"
                style="color: {{ $tintaHex[$categoria] }}">
                {{ $etiquetaActual }}
            </h2>
            <p class="mt-2 text-base text-gg-tinta leading-relaxed max-w-md">{{ $frases[$categoria] }}</p>
        </div>
        @if($codigoParticipante)
        <p class="shrink-0 font-mono text-xs text-gg-tinta-suave bg-gg-papel border border-gg-borde rounded-full px-2.5 py-1">
            Código: {{ $codigoParticipante }}
        </p>
        @endif
    </div>

    {{-- Pista de tres segmentos con probabilidad por nivel --}}
    <div role="img" aria-label="Resultado: {{ $etiquetaActual }}. Probabilidades: bajo {{ $probabilidades[0] }} %, medio {{ $probabilidades[1] }} %, alto {{ $probabilidades[2] }} %.">
        <div class="grid grid-cols-3 gap-2" aria-hidden="true">
            @foreach($etiquetas as $i => $etiqueta)
            @php $esActual = $categoria === $i; @endphp
            <div
                class="relative rounded-control px-3 pt-3 pb-3.5 transition-shadow
                       {{ $esActual ? '' : 'border border-gg-borde bg-gg-superficie' }}"
                style="{{ $esActual ? "background-color: {$bgSuave[$i]}; box-shadow: inset 0 0 0 2px {$bgHex[$i]};" : '' }}"
            >
                {{-- Marcador de posición actual --}}
                @if($esActual)
                <span class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-full pb-1">
                    <svg width="12" height="8" viewBox="0 0 12 8" fill="{{ $bgHex[$i] }}">
                        <polygon points="6,8 0,0 12,0"/>
                    </svg>
                </span>
                @endif

                <div class="flex items-baseline justify-between gap-1">
                    <span class="text-sm leading-tight {{ $esActual ? 'font-medium' : '' }}"
                          style="color: {{ $esActual ? $tintaHex[$i] : 'var(--gg-tinta-suave)' }}">
                        <span class="sm:hidden">{{ $cortas[$i] }}</span>
                        <span class="hidden sm:inline">{{ $etiqueta }}</span>
                    </span>
                    <span class="font-mono text-xs"
                          style="color: {{ $esActual ? $tintaHex[$i] : 'var(--gg-tinta-suave)' }}">{{ $probabilidades[$i] }}&thinsp;%</span>
                </div>

                {{-- Barra de probabilidad — se llena al cargar, escalonada por nivel --}}
                <div class="mt-2.5 h-1.5 rounded-full overflow-hidden" style="background-color: {{ $esActual ? 'rgba(255,255,255,.7)' : 'var(--gg-papel-hondo)' }}">
                    <div class="h-full rounded-full transition-[width] duration-700 ease-[cubic-bezier(0.22,1,0.36,1)]"
                         style="width: 0%; background-color: {{ $esActual ? $bgHex[$i] : '#B9C2BB' }};"
                         x-data x-init="setTimeout(() => $el.style.width = '{{ max($probabilidades[$i], 2) }}%', 120 + {{ $i }} * 80)"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Contribuciones principales --}}
    @if(count($contribuciones) > 0)
    <div class="pt-6 border-t border-gg-borde">
        <p class="text-base font-medium text-gg-tinta mb-1">
            Factores con mayor contribución al resultado:
        </p>
        <p class="text-sm text-gg-tinta-suave mb-4">
            Los valores ×N son odds ratios: indican cuántas veces aumenta la probabilidad relativa de esta categoría respecto a riesgo bajo.
        </p>
        <ol class="space-y-3" role="list">
            @foreach($contribuciones as $n => $c)
            <li class="grid grid-cols-[1.75rem_1fr_auto] items-center gap-x-3 gap-y-1.5">
                <span class="row-span-2 self-start w-7 h-7 rounded-full bg-gg-papel border border-gg-borde text-gg-tinta-suave font-mono text-xs inline-flex items-center justify-center" aria-hidden="true">{{ $n + 1 }}</span>
                <span class="text-sm sm:text-base text-gg-tinta leading-snug">{{ $c['etiqueta'] }}</span>
                <span class="font-mono text-sm text-gg-tinta whitespace-nowrap">
                    ×{{ number_format($c['odds'], 1) }}
                </span>
                <span class="col-span-2 h-1 rounded-full bg-gg-papel-hondo overflow-hidden" aria-hidden="true">
                    <span class="block h-full rounded-full bg-gg-primario/70"
                          style="width: {{ max(6, round($c['odds'] / $maxOdds * 100)) }}%"></span>
                </span>
            </li>
            @endforeach
        </ol>
    </div>
    @endif

    {{-- Aviso de no-diagnóstico — obligatorio por Res. 3100/2019 --}}
    <x-aviso-no-diagnostico />

</section>
