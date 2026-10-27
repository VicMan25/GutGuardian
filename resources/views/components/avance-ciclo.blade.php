{{--
    Avance del ciclo de la encuesta por jornadas, para superficies de marca
    (.gg-marca) de /inicio. Un segmento por día: completado, actual o futuro.

    Props:
      ciclo  array  Resultado de JornadaService::cicloAbierto()
--}}
@props(['ciclo'])

<div {{ $attributes }}>
    <div class="flex gap-1.5" aria-hidden="true">
        @for($d = 1; $d <= $ciclo['total']; $d++)
            <div class="h-1.5 flex-1 rounded-full {{ $d <= $ciclo['completadas'] ? 'bg-gg-acento' : ($d === $ciclo['jornada'] ? 'bg-white/45' : 'bg-white/15') }}"></div>
        @endfor
    </div>
    <p class="mt-2 text-sm text-white/70">
        {{ $ciclo['completadas'] }} de {{ $ciclo['total'] }} días completados
    </p>
</div>
