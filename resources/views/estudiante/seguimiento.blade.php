<x-layouts.estudiante titulo="Seguimiento">

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-medium text-gg-tinta">Seguimiento</h1>
            <p class="text-sm text-gg-tinta-suave mt-1">Evolución de tu riesgo y síntomas a lo largo del tiempo.</p>
        </div>
        <x-boton variante="secundario" href="{{ route('historial.show') }}" tamano="sm">
            Ver historial
        </x-boton>
    </div>

    @if(!$suficientesDatos)
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">Aún no hay suficientes datos</h2>
            <p class="text-sm text-gg-tinta-suave max-w-sm mb-8">
                Necesitas al menos dos encuestas completadas para ver tu evolución.
                Completa otra encuesta para desbloquear esta vista.
            </p>
            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}">Comenzar encuesta</x-boton>
        </x-tarjeta>
    @else

        {{-- Evolución del nivel de riesgo (HU-008) --}}
        <x-tarjeta class="mb-6">
            <h2 class="text-sm font-medium text-gg-tinta mb-4">Evolución del nivel de riesgo</h2>
            <div class="h-64">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @json($riesgo['etiquetas']),
                            datasets: [
                                { label: 'Riesgo bajo', data: @json($riesgo['bajo']), borderColor: '#3E7D64', backgroundColor: '#3E7D64', tension: 0.25 },
                                { label: 'Riesgo medio', data: @json($riesgo['medio']), borderColor: '#C08A2E', backgroundColor: '#C08A2E', tension: 0.25 },
                                { label: 'Riesgo alto', data: @json($riesgo['alto']), borderColor: '#9C4A32', backgroundColor: '#9C4A32', tension: 0.25 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de evolución de las probabilidades de riesgo bajo, medio y alto"
                ></canvas>
            </div>
        </x-tarjeta>

        {{-- Evolución del dolor abdominal (HU-013) --}}
        <x-tarjeta class="mb-6">
            <h2 class="text-sm font-medium text-gg-tinta mb-4">Evolución del dolor abdominal</h2>
            <div class="h-56">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @json($dolor['etiquetas']),
                            datasets: [
                                { label: 'Dolor (1-5)', data: @json($dolor['valores']), borderColor: '#1F5C4A', backgroundColor: '#1F5C4A', tension: 0.25 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { min: 1, max: 5, ticks: { stepSize: 1 } } },
                            plugins: { legend: { display: false } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de evolución de la intensidad del dolor abdominal"
                ></canvas>
            </div>
        </x-tarjeta>

        {{-- Seguimiento de síntomas (HU-011/HU-012) --}}
        <div class="mb-3">
            <h2 class="text-sm font-medium text-gg-tinta">Seguimiento de síntomas</h2>
            <p class="text-2xs text-gg-tinta-suave mt-0.5">
                Frecuencia en el último mes y temporalidad reportada, por síntoma.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            @foreach($sintomas as $panel)
            <x-tarjeta>
                <h3 class="text-xs font-medium text-gg-tinta mb-3">{{ $panel['sintoma'] }}</h3>
                <div class="h-40">
                    <canvas
                        x-data
                        x-init="new Chart($el, {
                            type: 'line',
                            data: {
                                labels: @json($panel['etiquetas']),
                                datasets: [
                                    { label: 'Frecuencia', data: @json($panel['frecuencia']), borderColor: '#2a78d6', backgroundColor: '#2a78d6', tension: 0.25 },
                                    { label: 'Temporalidad', data: @json($panel['temporalidad']), borderColor: '#4a3aa7', backgroundColor: '#4a3aa7', tension: 0.25 },
                                ],
                            },
                            options: {
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 10 } } } },
                            },
                        })"
                        role="img"
                        aria-label="Gráfica de frecuencia y temporalidad de {{ $panel['sintoma'] }}"
                    ></canvas>
                </div>
            </x-tarjeta>
            @endforeach
        </div>

    @endif

    <x-aviso-no-diagnostico />

</x-layouts.estudiante>
