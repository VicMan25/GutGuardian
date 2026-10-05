<x-layouts.profesional titulo="Panel institucional">

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="gg-rotulo">{{ ucfirst(now()->locale('es')->translatedFormat('l j \d\e F')) }}</p>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-medium text-gg-tinta">Panel institucional</h1>
            <p class="text-base text-gg-tinta-suave mt-2">
                Bienvenido, {{ Auth::user()->name }}. Resumen de la población monitoreada.
            </p>
        </div>
        @can('generar_reportes')
        <x-boton variante="primario" href="{{ route('panel.reportes.index') }}" icono="reportes">
            Generar reporte
        </x-boton>
        @endcan
    </div>

    @can('consultar_estudiantes')
    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        @foreach([
            ['valor' => $totalEstudiantes,     'etiqueta' => 'Estudiantes registrados', 'icono' => 'estudiantes'],
            ['valor' => $estudiantesActivos,   'etiqueta' => 'Cuentas activas',         'icono' => 'perfil'],
            ['valor' => $encuestasCompletadas, 'etiqueta' => 'Encuestas completadas',   'icono' => 'encuesta'],
            ['valor' => $totalEvaluaciones,    'etiqueta' => 'Evaluaciones de riesgo',  'icono' => 'resultado'],
        ] as $kpi)
        <div class="rounded-tarjeta p-4 sm:p-5 overflow-hidden {{ $loop->first ? 'gg-marca shadow-elev-2' : 'bg-gg-superficie border border-gg-borde shadow-elev-1' }}">
            <span class="w-9 h-9 rounded-control inline-flex items-center justify-center
                         {{ $loop->first ? 'bg-white/10 text-gg-acento' : 'bg-gg-primario-suave text-gg-primario' }}">
                <x-icono :nombre="$kpi['icono']" class="w-[18px] h-[18px]" />
            </span>
            <p class="mt-4 font-display text-3xl font-medium {{ $loop->first ? 'text-white' : 'text-gg-tinta' }}">{{ number_format($kpi['valor']) }}</p>
            <p class="text-sm mt-0.5 {{ $loop->first ? 'text-white/75' : 'text-gg-tinta-suave' }}">{{ $kpi['etiqueta'] }}</p>
        </div>
        @endforeach
    </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">

        {{-- Distribución de niveles de riesgo --}}
        @can('generar_reportes')
        <x-tarjeta class="lg:col-span-2">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h2 class="font-display text-xl font-medium text-gg-tinta">Distribución de niveles de riesgo</h2>
                    <p class="text-sm text-gg-tinta-suave mt-0.5">Todas las evaluaciones registradas en el sistema.</p>
                </div>
                <a href="{{ route('panel.reportes.index') }}"
                   class="inline-flex items-center gap-1 text-sm font-medium text-gg-primario hover:underline shrink-0">
                    Ver reporte completo
                    <x-icono nombre="flecha" class="w-4 h-4" />
                </a>
            </div>

            @if($totalEvaluaciones === 0)
                <div class="py-8 text-center">
                    <p class="text-base text-gg-tinta-suave">Todavía no hay evaluaciones de riesgo registradas.</p>
                </div>
            @else
                @php $bgHex = ['#3E7D64', '#C08A2E', '#9C4A32']; @endphp

                {{-- Barra apilada: la proporción completa de un vistazo --}}
                <div class="flex h-4 rounded-full overflow-hidden gap-0.5 bg-gg-papel-hondo mb-6" role="presentation">
                    @foreach($resumenRiesgo as $fila)
                        @if($fila['porcentaje'] > 0)
                        <div class="h-full" style="width: {{ $fila['porcentaje'] }}%; background-color: {{ $bgHex[$fila['categoria']] }};"></div>
                        @endif
                    @endforeach
                </div>

                <div class="grid sm:grid-cols-3 gap-3">
                    @foreach($resumenRiesgo as $fila)
                    <div class="rounded-control border border-gg-borde p-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $bgHex[$fila['categoria']] }}" aria-hidden="true"></span>
                            <span class="text-sm text-gg-tinta">{{ $fila['etiqueta'] }}</span>
                        </div>
                        <p class="mt-2 font-display text-2xl font-medium text-gg-tinta">{{ $fila['porcentaje'] }}%</p>
                        <p class="font-mono text-xs text-gg-tinta-suave">{{ $fila['total'] }} {{ $fila['total'] === 1 ? 'evaluación' : 'evaluaciones' }}</p>
                    </div>
                    @endforeach
                </div>
                <p class="text-sm text-gg-tinta-suave mt-5">
                    Sobre {{ number_format($totalEvaluaciones) }} evaluaciones. No constituye diagnóstico.
                </p>
            @endif
        </x-tarjeta>
        @endcan

        {{-- Accesos rápidos --}}
        <div class="grid gap-4 content-start">
            @can('consultar_estudiantes')
            <a href="{{ route('panel.estudiantes.index') }}" class="block group rounded-tarjeta">
                <x-tarjeta interactiva class="flex items-center gap-4">
                    <span class="shrink-0 w-11 h-11 rounded-control bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center">
                        <x-icono nombre="estudiantes" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-md font-medium text-gg-tinta">Estudiantes</p>
                        <p class="text-sm text-gg-tinta-suave mt-0.5">Consulta individual y listado general.</p>
                    </div>
                    <x-icono nombre="flecha" class="w-4 h-4 text-gg-tinta-suave transition-transform duration-200 group-hover:translate-x-0.5" />
                </x-tarjeta>
            </a>
            @endcan
            @can('generar_reportes')
            <a href="{{ route('panel.reportes.index') }}" class="block group rounded-tarjeta">
                <x-tarjeta interactiva class="flex items-center gap-4">
                    <span class="shrink-0 w-11 h-11 rounded-control bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center">
                        <x-icono nombre="reportes" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-md font-medium text-gg-tinta">Reportes</p>
                        <p class="text-sm text-gg-tinta-suave mt-0.5">Comportamiento general, filtrable y exportable.</p>
                    </div>
                    <x-icono nombre="flecha" class="w-4 h-4 text-gg-tinta-suave transition-transform duration-200 group-hover:translate-x-0.5" />
                </x-tarjeta>
            </a>
            @endcan
            @can('crear_usuarios')
            <a href="{{ route('panel.usuarios.create') }}" class="block group rounded-tarjeta">
                <x-tarjeta interactiva class="flex items-center gap-4">
                    <span class="shrink-0 w-11 h-11 rounded-control bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center">
                        <x-icono nombre="mas" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-display text-md font-medium text-gg-tinta">Nuevo estudiante</p>
                        <p class="text-sm text-gg-tinta-suave mt-0.5">Crear una cuenta de participante.</p>
                    </div>
                    <x-icono nombre="flecha" class="w-4 h-4 text-gg-tinta-suave transition-transform duration-200 group-hover:translate-x-0.5" />
                </x-tarjeta>
            </a>
            @endcan
        </div>

    </div>

</x-layouts.profesional>
