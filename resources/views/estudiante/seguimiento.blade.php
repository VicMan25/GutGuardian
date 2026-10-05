<x-layouts.estudiante titulo="Seguimiento" ancho="amplio">

    <div class="gg-entrada">

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl sm:text-4xl font-medium text-gg-tinta">Seguimiento</h1>
            <p class="text-base text-gg-tinta-suave mt-1">Evolución de tu riesgo y síntomas a lo largo del tiempo.</p>
        </div>
        <x-boton variante="secundario" href="{{ route('historial.show') }}" tamano="sm" icono="historial">
            Ver historial
        </x-boton>
    </div>

    @if(!$suficientesDatos)
        <x-tarjeta class="flex flex-col items-center text-center py-14 px-6 gg-flujo-claro overflow-hidden">
            <span class="w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center mb-5">
                <x-icono nombre="seguimiento" class="w-7 h-7" />
            </span>
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">Aún no hay suficientes datos</h2>
            <p class="text-base text-gg-tinta-suave max-w-sm mb-8">
                Necesitas al menos dos encuestas completadas para ver tu evolución.
                Completa otra encuesta para desbloquear esta vista.
            </p>
            <x-boton variante="primario" href="{{ route('encuesta.iniciar') }}" icono-final="flecha">Comenzar encuesta</x-boton>
        </x-tarjeta>
    @else

        <div class="grid gap-5 lg:grid-cols-3 mb-10">

        {{-- Evolución del nivel de riesgo (HU-008) --}}
        <x-tarjeta class="lg:col-span-2">
            <h2 class="font-display text-xl font-medium text-gg-tinta">Evolución del nivel de riesgo</h2>
            <p class="text-sm text-gg-tinta-suave mt-0.5 mb-5">Probabilidad de cada nivel en cada encuesta.</p>
            <div class="h-64 sm:h-72">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: {{ Js::from($riesgo['etiquetas']) }},
                            datasets: [
                                { label: 'Riesgo bajo', data: {{ Js::from($riesgo['bajo']) }}, borderColor: '#3E7D64', backgroundColor: '#3E7D64', tension: 0.35 },
                                { label: 'Riesgo medio', data: {{ Js::from($riesgo['medio']) }}, borderColor: '#C08A2E', backgroundColor: '#C08A2E', tension: 0.35 },
                                { label: 'Riesgo alto', data: {{ Js::from($riesgo['alto']) }}, borderColor: '#9C4A32', backgroundColor: '#9C4A32', tension: 0.35 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 16, font: { size: 13 } } } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de evolución de las probabilidades de riesgo bajo, medio y alto"
                ></canvas>
            </div>
        </x-tarjeta>

        {{-- Evolución del dolor abdominal (complementaria, sin HU numerada) --}}
        <x-tarjeta>
            <h2 class="font-display text-xl font-medium text-gg-tinta">Evolución del dolor abdominal</h2>
            <p class="text-sm text-gg-tinta-suave mt-0.5 mb-5">Escala de 1 (sin dolor) a 5 (muy intenso).</p>
            <div class="h-64 sm:h-72">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: {{ Js::from($dolor['etiquetas']) }},
                            datasets: [
                                { label: 'Dolor (1-5)', data: {{ Js::from($dolor['valores']) }}, borderColor: '#1F5C4A', backgroundColor: '#1F5C4A', tension: 0.35 },
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

        </div>

        {{-- Seguimiento de síntomas (complementaria, sin HU numerada) --}}
        <div class="mb-4">
            <h2 class="font-display text-xl font-medium text-gg-tinta">Seguimiento de síntomas</h2>
            <p class="text-sm text-gg-tinta-suave mt-0.5">
                Frecuencia en el último mes y temporalidad reportada, por síntoma.
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            @foreach($sintomas as $panel)
            <x-tarjeta>
                <h3 class="text-base font-medium text-gg-tinta mb-3">{{ $panel['sintoma'] }}</h3>
                <div class="h-44">
                    <canvas
                        x-data
                        x-init="new Chart($el, {
                            type: 'line',
                            data: {
                                labels: {{ Js::from($panel['etiquetas']) }},
                                datasets: [
                                    { label: 'Frecuencia', data: {{ Js::from($panel['frecuencia']) }}, borderColor: '#2a78d6', backgroundColor: '#2a78d6', tension: 0.35 },
                                    { label: 'Temporalidad', data: {{ Js::from($panel['temporalidad']) }}, borderColor: '#4a3aa7', backgroundColor: '#4a3aa7', tension: 0.35 },
                                ],
                            },
                            options: {
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 12, font: { size: 12 } } } },
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

    </div>

</x-layouts.estudiante>
