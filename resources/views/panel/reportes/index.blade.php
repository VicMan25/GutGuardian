<x-layouts.profesional titulo="Reportes">

    <x-slot:acciones>
        @can('exportar_reportes')
        <x-boton variante="secundario" tamano="sm" href="{{ route('panel.reportes.exportarPdf', $filtros) }}">PDF</x-boton>
        <x-boton variante="secundario" tamano="sm" href="{{ route('panel.reportes.exportarExcel', $filtros) }}">Excel</x-boton>
        @endcan
    </x-slot:acciones>

    <x-tarjeta class="mb-6">
        <form method="GET" action="{{ route('panel.reportes.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="space-y-1.5">
                <label for="desde" class="block text-2xs font-medium text-gg-tinta-suave">Desde</label>
                <input id="desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                       class="rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie px-3 py-1.5 focus:outline-none focus:border-gg-primario" />
            </div>
            <div class="space-y-1.5">
                <label for="hasta" class="block text-2xs font-medium text-gg-tinta-suave">Hasta</label>
                <input id="hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                       class="rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie px-3 py-1.5 focus:outline-none focus:border-gg-primario" />
            </div>
            <div class="space-y-1.5">
                <label for="categoria" class="block text-2xs font-medium text-gg-tinta-suave">Nivel de riesgo</label>
                <select id="categoria" name="categoria"
                        class="rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie px-3 py-1.5 focus:outline-none focus:border-gg-primario">
                    <option value="">Todos</option>
                    <option value="0" {{ ($filtros['categoria'] ?? '') === '0' ? 'selected' : '' }}>Riesgo bajo</option>
                    <option value="1" {{ ($filtros['categoria'] ?? '') === '1' ? 'selected' : '' }}>Riesgo medio</option>
                    <option value="2" {{ ($filtros['categoria'] ?? '') === '2' ? 'selected' : '' }}>Riesgo alto</option>
                </select>
            </div>
            <x-boton variante="primario" tipo="submit" tamano="sm">Filtrar</x-boton>
            @if(($filtros['desde'] ?? null) || ($filtros['hasta'] ?? null) || ($filtros['categoria'] ?? null))
                <x-boton variante="fantasma" tamano="sm" href="{{ route('panel.reportes.index') }}">Limpiar</x-boton>
            @endif
        </form>
    </x-tarjeta>

    @if($total === 0)
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">
            <p class="text-sm text-gg-tinta-suave">No hay evaluaciones que coincidan con los filtros seleccionados.</p>
        </x-tarjeta>
    @else
        @php $bgHex = ['#3E7D64', '#C08A2E', '#9C4A32']; @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @foreach($resumen as $fila)
            <x-tarjeta>
                <p class="text-2xs text-gg-tinta-suave mb-1">{{ $fila['etiqueta'] }}</p>
                <p class="font-display text-2xl font-medium" style="color: {{ $bgHex[$fila['categoria']] }}">
                    {{ $fila['total'] }}
                </p>
                <p class="text-2xs text-gg-tinta-suave mt-0.5">{{ $fila['porcentaje'] }}% del total</p>
            </x-tarjeta>
            @endforeach
        </div>

        <x-tarjeta class="mb-6">
            <h2 class="text-sm font-medium text-gg-tinta mb-4">Distribución de niveles de riesgo</h2>
            <div class="h-64 max-w-md">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'bar',
                        data: {
                            labels: @json(collect($resumen)->pluck('etiqueta')),
                            datasets: [{
                                data: @json(collect($resumen)->pluck('total')),
                                backgroundColor: @json($bgHex),
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

        <x-tarjeta>
            <h2 class="text-sm font-medium text-gg-tinta mb-4">Detalle ({{ $total }} evaluaciones)</h2>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="text-left text-2xs text-gg-tinta-suave uppercase tracking-wide border-b border-gg-borde">
                        <th class="py-2 pr-4 font-medium">Estudiante</th>
                        <th class="py-2 pr-4 font-medium">Código</th>
                        <th class="py-2 pr-4 font-medium">Programa</th>
                        <th class="py-2 pr-4 font-medium">Riesgo</th>
                        <th class="py-2 pr-4 font-medium">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalle as $fila)
                    <tr class="border-b border-gg-borde">
                        <td class="py-2 pr-4">{{ $fila['estudiante'] }}</td>
                        <td class="py-2 pr-4 font-mono text-2xs text-gg-tinta-suave">{{ $fila['codigo_participante'] }}</td>
                        <td class="py-2 pr-4 text-gg-tinta-suave">{{ $fila['programa'] }}</td>
                        <td class="py-2 pr-4">{{ $fila['categoria'] }}</td>
                        <td class="py-2 pr-4 text-gg-tinta-suave">{{ $fila['fecha'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </x-tarjeta>
    @endif

</x-layouts.profesional>
