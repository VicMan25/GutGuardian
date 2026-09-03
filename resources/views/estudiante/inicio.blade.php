<x-layouts.estudiante titulo="Inicio">

    {{-- Saludo --}}
    <div class="mb-8">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">
            Hola, {{ Auth::user()->name }}
        </h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Aquí encontrarás tu historial de encuestas y tu nivel de riesgo digestivo.
        </p>
    </div>

    @if($totalCompletados === 0)
        {{-- Estado vacío --}}
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">

            {{-- Ícono decorativo --}}
            <svg class="w-12 h-12 text-gg-borde mb-5" viewBox="0 0 48 48" fill="none"
                 stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="8" y="12" width="32" height="28" rx="3"/>
                <path d="M16 12V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/>
                <path d="M18 24h12M18 31h8"/>
            </svg>

            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">
                Aún no tienes registros
            </h2>
            <p class="text-sm text-gg-tinta-suave max-w-xs mb-8">
                Completa tu primera encuesta para conocer tu nivel de riesgo digestivo
                y recibir orientación de autocuidado.
            </p>

            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}">
                Comenzar encuesta
            </x-boton>

        </x-tarjeta>
    @else
        @php
        $etiquetasCategoria = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
        $bgHex = ['#3E7D64', '#C08A2E', '#9C4A32'];
        $evaluacion = $ultimoDiligenciamiento->evaluaciones->first();
        @endphp

        {{-- Resultado más reciente --}}
        <x-tarjeta padding="p-0" class="mb-4 overflow-hidden">
            <div class="flex">
                <div class="w-1.5 shrink-0"
                     style="background-color: {{ $evaluacion ? $bgHex[$evaluacion->categoria] : '#E0E3DC' }};"
                     aria-hidden="true"></div>
                <div class="flex-1 p-5">
                    <p class="text-xs text-gg-tinta-suave mb-1">Tu resultado más reciente</p>
                    @if($evaluacion)
                        <p class="font-display text-2xl font-medium" style="color: {{ $bgHex[$evaluacion->categoria] }}">
                            {{ $etiquetasCategoria[$evaluacion->categoria] }}
                        </p>
                        <p class="text-2xs text-gg-tinta-suave mt-1 mb-4">
                            Evaluado el {{ $evaluacion->evaluado_at->format('d/m/Y') }}
                            · {{ $totalCompletados }} {{ $totalCompletados === 1 ? 'encuesta completada' : 'encuestas completadas' }}
                        </p>
                    @else
                        <p class="text-sm text-gg-tinta-suave mt-1 mb-4">Aún no se ha calculado.</p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        <x-boton variante="primario" href="{{ route('resultado.show', $ultimoDiligenciamiento) }}" tamano="sm">
                            Ver resultado
                        </x-boton>
                        <x-boton variante="secundario" href="{{ route('historial.show') }}" tamano="sm">
                            Historial
                        </x-boton>
                        <x-boton variante="secundario" href="{{ route('seguimiento.show') }}" tamano="sm">
                            Seguimiento
                        </x-boton>
                    </div>
                </div>
            </div>
        </x-tarjeta>

        {{-- Nueva encuesta --}}
        <x-tarjeta class="flex items-center justify-between gap-4 mb-4">
            <div>
                <p class="text-sm font-medium text-gg-tinta">¿Listo para una nueva encuesta?</p>
                <p class="text-2xs text-gg-tinta-suave mt-0.5">
                    Vuelve a diligenciar el instrumento para ver tu evolución.
                </p>
            </div>
            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}" tamano="sm">
                Comenzar
            </x-boton>
        </x-tarjeta>
    @endif

    {{-- Aviso obligatorio Res. 3100 de 2019 --}}
    <div class="mt-6">
        <x-aviso-no-diagnostico />
    </div>

</x-layouts.estudiante>
