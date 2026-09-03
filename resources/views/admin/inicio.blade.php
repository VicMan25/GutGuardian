<x-layouts.profesional titulo="Administración">

    <h1 class="font-display text-2xl font-medium text-gg-tinta mb-2">Administración</h1>
    <p class="text-sm text-gg-tinta-suave">Bienvenido, {{ Auth::user()->name }}.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
        <a href="{{ route('panel.inicio') }}" class="block">
            <x-tarjeta class="hover:bg-gg-papel transition-colors duration-100">
                <p class="font-display text-md font-medium text-gg-tinta">Panel institucional</p>
                <p class="text-2xs text-gg-tinta-suave mt-1">Indicadores, estudiantes y reportes.</p>
            </x-tarjeta>
        </a>
        <x-tarjeta class="flex flex-col justify-center">
            <p class="text-sm font-medium text-gg-tinta">Gestión del modelo predictivo</p>
            <p class="text-2xs text-gg-tinta-suave mt-1">
                Versionado de <code class="font-mono">versiones_modelo</code> — pendiente de sprint posterior.
            </p>
        </x-tarjeta>
    </div>

</x-layouts.profesional>
