<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titulo ?? 'Encuesta' }} — GutGuardián</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-full font-sans antialiased bg-gg-papel text-gg-tinta">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3
              focus:bg-gg-superficie focus:border focus:border-gg-primario focus:rounded-control
              focus:px-3 focus:py-2 focus:text-sm focus:text-gg-primario">
        Saltar al contenido
    </a>

    {{-- Barra de navegación mínima --}}
    <header class="sticky top-0 z-20 bg-gg-superficie border-b border-gg-borde">
        <div class="max-w-estudiante mx-auto px-4 h-14 flex items-center justify-between gap-4">

            <a href="{{ route('estudiante.inicio') }}"
               class="font-display text-md font-medium text-gg-primario tracking-tight shrink-0"
               aria-label="GutGuardián — inicio">
                GutGuardián
            </a>

            @if(isset($progreso))
                <div class="flex-1 min-w-0">
                    {{ $progreso }}
                </div>
            @endif

            {{-- Usuario + cerrar sesión --}}
            <div class="shrink-0 flex items-center gap-3">
                @auth
                <span class="hidden sm:block text-xs text-gg-tinta-suave truncate max-w-[160px]">
                    {{ Auth::user()->name }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-xs text-gg-tinta-suave hover:text-gg-tinta transition-colors duration-150">
                        Salir
                    </button>
                </form>
                @endauth
            </div>

        </div>

        {{--
            Navbar dinámico: siempre visible (incluso al confirmar/ver el
            resultado, donde antes no había forma de volver al panel de
            inicio salvo el logo). Resalta la sección activa con
            request()->routeIs(), igual que layouts/profesional.
        --}}
        @auth
        @php $alertasNoLeidas = Auth::user()->alertas()->whereNull('leida_at')->count(); @endphp
        <nav aria-label="Navegación principal" class="max-w-estudiante mx-auto px-4 pb-2 flex items-center gap-1 overflow-x-auto">
            @foreach([
                ['ruta' => 'estudiante.inicio', 'etiqueta' => 'Inicio'],
                ['ruta' => 'historial.show', 'etiqueta' => 'Historial'],
                ['ruta' => 'seguimiento.show', 'etiqueta' => 'Seguimiento'],
                ['ruta' => 'alertas.index', 'etiqueta' => 'Alertas'],
                ['ruta' => 'perfil.edit', 'etiqueta' => 'Perfil'],
            ] as $item)
                @php $activo = request()->routeIs($item['ruta']); @endphp
                <a
                    href="{{ route($item['ruta']) }}"
                    @if($activo) aria-current="page" @endif
                    class="shrink-0 px-3 py-1.5 rounded-control text-sm transition-colors duration-100 inline-flex items-center gap-1.5
                           {{ $activo
                               ? 'bg-gg-primario-suave text-gg-primario font-medium'
                               : 'text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel' }}"
                >
                    {{ $item['etiqueta'] }}
                    @if($item['ruta'] === 'alertas.index' && $alertasNoLeidas > 0)
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-2xs font-medium bg-gg-primario text-white">
                            {{ $alertasNoLeidas }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
        @endauth
    </header>

    {{-- Contenido principal — columna única, ancho máximo 640 px --}}
    <main id="contenido" class="max-w-estudiante mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    {{-- Pie: aviso legal siempre visible --}}
    <footer class="max-w-estudiante mx-auto px-4 pb-10">
        <p class="text-2xs text-gg-tinta-suave text-center">
            GutGuardián no emite diagnóstico clínico.
            Res. 3100 de 2019 · Universidad Mariana, Pasto.
        </p>
    </footer>

</body>
</html>
