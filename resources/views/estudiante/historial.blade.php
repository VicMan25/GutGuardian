<x-layouts.estudiante titulo="Historial">

    <div class="gg-entrada">

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-medium text-gg-tinta">Historial</h1>
            <p class="text-base text-gg-tinta-suave mt-1">Tus encuestas completadas, de la más reciente a la más antigua.</p>
        </div>
        <x-boton variante="secundario" href="{{ route('seguimiento.show') }}" tamano="sm" icono="seguimiento">
            Ver seguimiento
        </x-boton>
    </div>

    @if($diligenciamientos->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-14 px-6 gg-flujo-claro overflow-hidden">
            <span class="w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center mb-5">
                <x-icono nombre="historial" class="w-7 h-7" />
            </span>
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">Aún no tienes encuestas completadas</h2>
            <p class="text-base text-gg-tinta-suave max-w-xs mb-8">
                Cuando completes tu primera encuesta, aparecerá aquí.
            </p>
            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}" icono-final="flecha">Comenzar encuesta</x-boton>
        </x-tarjeta>
    @else
        {{-- Línea de tiempo: cada encuesta es un hito --}}
        <ol class="relative space-y-3 before:absolute before:left-[19px] before:top-4 before:bottom-4 before:w-px before:bg-gg-borde" role="list">
            @foreach($diligenciamientos as $diligenciamiento)
            @php $evaluacion = $diligenciamiento->evaluaciones->first(); @endphp
            <li class="relative pl-12">
                <span class="absolute left-[13px] top-1/2 -translate-y-1/2 w-[13px] h-[13px] rounded-full border-2 border-gg-papel
                             {{ $loop->first ? 'bg-gg-primario ring-4 ring-gg-primario-suave' : 'bg-[#B9C2BB]' }}" aria-hidden="true"></span>
                <a
                    href="{{ route('resultado.show', $diligenciamiento) }}"
                    class="group flex items-center justify-between gap-4 p-4 sm:p-5 bg-gg-superficie border border-gg-borde rounded-tarjeta shadow-elev-1 gg-interactiva"
                >
                    <div class="min-w-0">
                        <p class="text-base font-medium text-gg-tinta">
                            <time datetime="{{ $diligenciamiento->completado_at->toDateString() }}">
                                {{ $diligenciamiento->completado_at->format('d/m/Y') }}
                            </time>
                            @if($loop->first)
                                <span class="ml-1.5 text-xs font-normal text-gg-primario">Más reciente</span>
                            @endif
                        </p>
                        <p class="text-sm text-gg-tinta-suave mt-0.5 truncate">
                            {{ $diligenciamiento->instrumento->nombre }}
                        </p>
                    </div>

                    <div class="shrink-0 flex items-center gap-3">
                        @if($evaluacion)
                            <x-insignia-riesgo :categoria="$evaluacion->categoria" />
                        @else
                            <span class="text-sm text-gg-tinta-suave">Ver resultado</span>
                        @endif
                        <x-icono nombre="flecha" class="hidden sm:block w-4 h-4 text-gg-tinta-suave transition-transform duration-200 group-hover:translate-x-0.5" />
                    </div>
                </a>
            </li>
            @endforeach
        </ol>
    @endif

    <div class="mt-8">
        <x-aviso-no-diagnostico />
    </div>

    </div>

</x-layouts.estudiante>
