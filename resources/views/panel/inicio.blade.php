<x-layouts.profesional titulo="Panel institucional">

    <h1 class="font-display text-2xl font-medium text-gg-tinta mb-2">Panel institucional</h1>
    <p class="text-sm text-gg-tinta-suave">Bienvenido, {{ Auth::user()->name }}.</p>

    {{-- Sprint 5 implementará este módulo completo --}}
    <x-tarjeta class="mt-6 py-10 flex flex-col items-center text-center">
        <p class="text-sm text-gg-tinta-suave">Módulo en construcción — Sprint 5.</p>
    </x-tarjeta>

</x-layouts.profesional>
