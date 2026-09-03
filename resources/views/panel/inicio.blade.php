<x-layouts.profesional titulo="Panel institucional">

    <h1 class="font-display text-2xl font-medium text-gg-tinta mb-2">Panel institucional</h1>
    <p class="text-sm text-gg-tinta-suave mb-6">Bienvenido, {{ Auth::user()->name }}.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
        @can('consultar_estudiantes')
        <a href="{{ route('panel.estudiantes.index') }}">
            <x-tarjeta class="hover:bg-gg-papel transition-colors duration-100">
                <p class="font-display text-lg font-medium text-gg-tinta">Estudiantes</p>
                <p class="text-sm text-gg-tinta-suave mt-1">Consulta individual y listado general, con su nivel de riesgo actual.</p>
            </x-tarjeta>
        </a>
        @endcan

        @can('generar_reportes')
        <a href="{{ route('panel.reportes.index') }}">
            <x-tarjeta class="hover:bg-gg-papel transition-colors duration-100">
                <p class="font-display text-lg font-medium text-gg-tinta">Reportes</p>
                <p class="text-sm text-gg-tinta-suave mt-1">Comportamiento general de los niveles de riesgo, filtrable y exportable.</p>
            </x-tarjeta>
        </a>
        @endcan
    </div>

</x-layouts.profesional>
