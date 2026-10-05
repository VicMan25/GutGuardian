<x-layouts.profesional titulo="Administración">

    <h1 class="font-display text-3xl font-medium text-gg-tinta mb-2">Administración</h1>
    <p class="text-sm text-gg-tinta-suave">Bienvenido, {{ Auth::user()->name }}.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
        @foreach([
            ['ruta' => 'panel.inicio', 'titulo' => 'Panel institucional', 'texto' => 'Indicadores, estudiantes y reportes.'],
            ['ruta' => 'admin.modelos.index', 'titulo' => 'Modelo predictivo', 'texto' => 'Registrar, revisar y activar versiones del modelo.'],
            ['ruta' => 'admin.validacion.index', 'titulo' => 'Validación', 'texto' => 'Usabilidad (SUS), tiempos y errores de la prueba piloto.'],
            ['ruta' => 'admin.auditoria.index', 'titulo' => 'Auditoría', 'texto' => 'Accesos a datos clínicos y cambios del modelo.'],
        ] as $tarjeta)
        <a href="{{ route($tarjeta['ruta']) }}" class="block">
            <x-tarjeta class="h-full hover:bg-gg-papel transition-colors duration-100">
                <p class="font-display text-md font-medium text-gg-tinta">{{ $tarjeta['titulo'] }}</p>
                <p class="text-xs text-gg-tinta-suave mt-1">{{ $tarjeta['texto'] }}</p>
            </x-tarjeta>
        </a>
        @endforeach
    </div>

</x-layouts.profesional>
