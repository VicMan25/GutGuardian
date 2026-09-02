<x-layouts.profesional titulo="Panel institucional">

    <div class="mb-8">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Panel institucional</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Bienvenido, {{ Auth::user()->name }}. Resumen de la población monitoreada.
        </p>
    </div>

    @can('consultar_estudiantes')
    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['valor' => $totalEstudiantes,     'etiqueta' => 'Estudiantes registrados'],
            ['valor' => $estudiantesActivos,   'etiqueta' => 'Cuentas activas'],
            ['valor' => $encuestasCompletadas, 'etiqueta' => 'Encuestas completadas'],
            ['valor' => $totalEvaluaciones,    'etiqueta' => 'Evaluaciones de riesgo'],
        ] as $kpi)
        <x-tarjeta padding="p-4">
            <p class="font-display text-2xl font-medium text-gg-tinta">{{ number_format($kpi['valor']) }}</p>
            <p class="text-2xs text-gg-tinta-suave mt-1">{{ $kpi['etiqueta'] }}</p>
        </x-tarjeta>
        @endforeach
    </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Distribución de niveles de riesgo --}}
        @can('generar_reportes')
        <x-tarjeta class="lg:col-span-2">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-sm font-medium text-gg-tinta">Distribución de niveles de riesgo</h2>
                <a href="{{ route('panel.reportes.index') }}"
                   class="text-xs text-gg-primario hover:underline shrink-0">Ver reporte completo</a>
            </div>

            @if($totalEvaluaciones === 0)
                <p class="text-sm text-gg-tinta-suave">Todavía no hay evaluaciones de riesgo registradas.</p>
            @else
                @php $bgHex = ['#3E7D64', '#C08A2E', '#9C4A32']; @endphp
                <div class="space-y-4">
                    @foreach($resumenRiesgo as $fila)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-gg-tinta">{{ $fila['etiqueta'] }}</span>
                            <span class="font-mono text-gg-tinta-suave">{{ $fila['total'] }} · {{ $fila['porcentaje'] }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-gg-papel overflow-hidden" role="presentation">
                            <div class="h-full rounded-full"
                                 style="width: {{ max($fila['porcentaje'], 1.5) }}%; background-color: {{ $bgHex[$fila['categoria']] }};"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="text-2xs text-gg-tinta-suave mt-4">
                    Sobre {{ number_format($totalEvaluaciones) }} evaluaciones. No constituye diagnóstico.
                </p>
            @endif
        </x-tarjeta>
        @endcan

        {{-- Accesos rápidos --}}
        <div class="space-y-4">
            @can('consultar_estudiantes')
            <a href="{{ route('panel.estudiantes.index') }}" class="block">
                <x-tarjeta class="hover:bg-gg-papel transition-colors duration-100">
                    <p class="font-display text-md font-medium text-gg-tinta">Estudiantes</p>
                    <p class="text-2xs text-gg-tinta-suave mt-1">Consulta individual y listado general.</p>
                </x-tarjeta>
            </a>
            @endcan
            @can('generar_reportes')
            <a href="{{ route('panel.reportes.index') }}" class="block">
                <x-tarjeta class="hover:bg-gg-papel transition-colors duration-100">
                    <p class="font-display text-md font-medium text-gg-tinta">Reportes</p>
                    <p class="text-2xs text-gg-tinta-suave mt-1">Comportamiento general, filtrable y exportable.</p>
                </x-tarjeta>
            </a>
            @endcan
        </div>

    </div>

</x-layouts.profesional>
