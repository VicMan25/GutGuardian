<x-layouts.profesional titulo="Reportes">

    <x-slot:acciones>
        @can('exportar_reportes')
        <x-boton variante="secundario" tamano="sm" href="{{ route('panel.reportes.exportarPdf', $filtros) }}" icono="descarga">PDF</x-boton>
        <x-boton variante="secundario" tamano="sm" href="{{ route('panel.reportes.exportarExcel', $filtros) }}" icono="descarga">Excel</x-boton>
        @endcan
    </x-slot:acciones>

    <div class="mb-6">
        <h2 class="font-display text-3xl font-medium text-gg-tinta">Distribución del riesgo</h2>
        <p class="text-base text-gg-tinta-suave mt-1">Filtra por periodo y nivel; las exportaciones respetan los filtros aplicados.</p>
    </div>

    <x-tarjeta class="mb-6" padding="p-4 sm:p-5">
        <form method="GET" action="{{ route('panel.reportes.index') }}" class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-3 sm:gap-4">
            <div class="space-y-1.5">
                <label for="desde" class="block text-sm font-medium text-gg-tinta">Desde</label>
                <input id="desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                       class="w-full sm:w-auto rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie px-3" />
            </div>
            <div class="space-y-1.5">
                <label for="hasta" class="block text-sm font-medium text-gg-tinta">Hasta</label>
                <input id="hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                       class="w-full sm:w-auto rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie px-3" />
            </div>
            <div class="col-span-2 sm:col-span-1 space-y-1.5">
                <label for="categoria" class="block text-sm font-medium text-gg-tinta">Nivel de riesgo</label>
                <select id="categoria" name="categoria"
                        class="w-full sm:w-auto sm:min-w-[180px] rounded-control border border-gg-borde text-gg-tinta bg-gg-superficie pl-3 pr-9">
                    <option value="">Todos</option>
                    <option value="0" {{ ($filtros['categoria'] ?? '') === '0' ? 'selected' : '' }}>Riesgo bajo</option>
                    <option value="1" {{ ($filtros['categoria'] ?? '') === '1' ? 'selected' : '' }}>Riesgo medio</option>
                    <option value="2" {{ ($filtros['categoria'] ?? '') === '2' ? 'selected' : '' }}>Riesgo alto</option>
                </select>
            </div>
            <x-boton variante="primario" tipo="submit" icono="buscar">Filtrar</x-boton>
            @if(($filtros['desde'] ?? null) || ($filtros['hasta'] ?? null) || ($filtros['categoria'] ?? null))
                <x-boton variante="fantasma" href="{{ route('panel.reportes.index') }}">Limpiar</x-boton>
            @endif
        </form>
    </x-tarjeta>

    @if($total === 0)
        <x-tarjeta class="flex flex-col items-center text-center py-14 px-6">
            <span class="w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center mb-5">
                <x-icono nombre="reportes" class="w-7 h-7" />
            </span>
            <p class="text-base text-gg-tinta-suave">No hay evaluaciones que coincidan con los filtros seleccionados.</p>
        </x-tarjeta>
    @else
        @php $bgHex = ['#3E7D64', '#C08A2E', '#9C4A32']; @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @foreach($resumen as $fila)
            <x-tarjeta class="relative overflow-hidden">
                <span class="absolute inset-x-0 top-0 h-1" style="background-color: {{ $bgHex[$fila['categoria']] }}" aria-hidden="true"></span>
                <p class="flex items-center gap-2 text-sm text-gg-tinta">
                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $bgHex[$fila['categoria']] }}" aria-hidden="true"></span>
                    {{ $fila['etiqueta'] }}
                </p>
                <p class="mt-3 font-display text-4xl font-medium text-gg-tinta">
                    {{ $fila['total'] }}
                </p>
                <p class="text-sm text-gg-tinta-suave mt-1">{{ $fila['porcentaje'] }}% del total</p>
            </x-tarjeta>
            @endforeach
        </div>

        <x-tarjeta class="mb-6">
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-5">Distribución de niveles de riesgo</h2>
            <div class="h-64 sm:h-72 max-w-2xl">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'bar',
                        data: {
                            labels: {{ Js::from(collect($resumen)->pluck('etiqueta')) }},
                            datasets: [{
                                data: {{ Js::from(collect($resumen)->pluck('total')) }},
                                backgroundColor: {{ Js::from($bgHex) }},
                                maxBarThickness: 72,
                            }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                            plugins: { legend: { display: false } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de barras con la distribución de niveles de riesgo"
                ></canvas>
            </div>
        </x-tarjeta>

        <x-tarjeta padding="p-0" class="overflow-hidden">
            <h2 class="font-display text-xl font-medium text-gg-tinta px-5 sm:px-6 pt-5 sm:pt-6 pb-4">Detalle ({{ $total }} evaluaciones)</h2>
            <div class="overflow-x-auto max-h-[560px]">
            <table class="gg-tabla">
                <thead>
                    <tr>
                        <th scope="col">Estudiante</th>
                        <th scope="col">Código</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Riesgo</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalle as $fila)
                    <tr>
                        <td class="whitespace-nowrap">{{ $fila['estudiante'] }}</td>
                        <td class="font-mono text-xs text-gg-tinta-suave">{{ $fila['codigo_participante'] }}</td>
                        <td class="text-gg-tinta-suave">{{ $fila['programa'] }}</td>
                        <td class="whitespace-nowrap">{{ $fila['categoria'] }}</td>
                        <td class="text-gg-tinta-suave whitespace-nowrap">{{ $fila['fecha'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </x-tarjeta>

        {{-- Resolución 3100 de 2019 — aviso obligatorio junto a todo resultado de riesgo. --}}
        <div class="mt-6">
            <x-aviso-no-diagnostico />
        </div>
    @endif

</x-layouts.profesional>
