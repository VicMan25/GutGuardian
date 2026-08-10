<x-layouts.estudiante titulo="Inicio">

    {{-- Saludo --}}
    <div class="mb-8">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">
            Hola, {{ Auth::user()->name }}
        </h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Aquí encontrarás tu historial de encuestas y tu nivel de riesgo digestivo.
        </p>
    </div>

    {{-- Estado vacío --}}
    <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">

        {{-- Ícono decorativo --}}
        <svg class="w-12 h-12 text-gg-borde mb-5" viewBox="0 0 48 48" fill="none"
             stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <rect x="8" y="12" width="32" height="28" rx="3"/>
            <path d="M16 12V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/>
            <path d="M18 24h12M18 31h8"/>
        </svg>

        <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">
            Aún no tienes registros
        </h2>
        <p class="text-sm text-gg-tinta-suave max-w-xs mb-8">
            Completa tu primera encuesta para conocer tu nivel de riesgo digestivo
            y recibir orientación de autocuidado.
        </p>

        <x-boton variante="primario" href="#">
            Comenzar encuesta
        </x-boton>

    </x-tarjeta>

    {{-- Aviso obligatorio Res. 3100 de 2019 --}}
    <div class="mt-6">
        <x-aviso-no-diagnostico />
    </div>

</x-layouts.estudiante>
