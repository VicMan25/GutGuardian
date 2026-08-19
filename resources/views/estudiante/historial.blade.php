<x-layouts.estudiante titulo="Historial">

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-medium text-gg-tinta">Historial</h1>
            <p class="text-sm text-gg-tinta-suave mt-1">Tus encuestas completadas, de la más reciente a la más antigua.</p>
        </div>
        <x-boton variante="secundario" href="{{ route('seguimiento.show') }}" tamano="sm">
            Ver seguimiento
        </x-boton>
    </div>

    @if($diligenciamientos->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">Aún no tienes encuestas completadas</h2>
            <p class="text-sm text-gg-tinta-suave max-w-xs mb-8">
                Cuando completes tu primera encuesta, aparecerá aquí.
            </p>
            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}">Comenzar encuesta</x-boton>
        </x-tarjeta>
    @else
        @php
        $etiquetasCategoria = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
        $bgHex   = ['#3E7D64', '#C08A2E', '#9C4A32'];
        $bgSuave = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];
        @endphp
        <ul class="space-y-3" role="list">
            @foreach($diligenciamientos as $diligenciamiento)
            @php $evaluacion = $diligenciamiento->evaluaciones->first(); @endphp
            <li>
                <a
                    href="{{ route('resultado.show', $diligenciamiento) }}"
                    class="flex items-center justify-between gap-4 p-4 bg-gg-superficie border border-gg-borde rounded-tarjeta
                           hover:bg-gg-papel transition-colors duration-100"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gg-tinta">
                            <time datetime="{{ $diligenciamiento->completado_at->toDateString() }}">
                                {{ $diligenciamiento->completado_at->format('d/m/Y') }}
                            </time>
                        </p>
                        <p class="text-2xs text-gg-tinta-suave mt-0.5">
                            {{ $diligenciamiento->instrumento->nombre }}
                        </p>
                    </div>

                    @if($evaluacion)
                        <span
                            class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-control"
                            style="color: {{ $bgHex[$evaluacion->categoria] }}; background-color: {{ $bgSuave[$evaluacion->categoria] }};"
                        >
                            {{ $etiquetasCategoria[$evaluacion->categoria] }}
                        </span>
                    @else
                        <span class="shrink-0 text-xs text-gg-tinta-suave">Ver resultado</span>
                    @endif
                </a>
            </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6">
        <x-aviso-no-diagnostico />
    </div>

</x-layouts.estudiante>
