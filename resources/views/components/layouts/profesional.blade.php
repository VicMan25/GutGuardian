<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titulo ?? 'Panel' }} — GutGuardián</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="h-full font-sans antialiased bg-gg-papel text-gg-tinta" x-data="{ navAbierta: false }">

    <div class="flex h-full">

        {{-- Sidebar fijo en desktop, drawer en móvil --}}
        <aside
            class="fixed inset-y-0 left-0 z-30 w-60 bg-gg-superficie border-r border-gg-borde flex flex-col
                   transition-transform duration-200
                   lg:translate-x-0 lg:static lg:inset-auto lg:h-full"
            :class="navAbierta ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            aria-label="Navegación principal"
        >
            {{-- Logo --}}
            <div class="h-14 flex items-center px-5 border-b border-gg-borde shrink-0">
                <a href="{{ route('panel.inicio') }}"
                   class="font-display text-md font-medium text-gg-primario tracking-tight">
                    GutGuardián
                </a>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5" aria-label="Menú de sección">

                @php
                $navItems = [
                    ['label' => 'Panel',        'route' => 'panel.inicio', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['label' => 'Estudiantes',  'route' => 'panel.inicio', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['label' => 'Reportes',     'route' => 'panel.inicio', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ];
                @endphp

                @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-control text-sm
                          transition-colors duration-150
                          {{ request()->routeIs($item['route'])
                             ? 'bg-gg-primario-suave text-gg-primario font-medium'
                             : 'text-gg-tinta-suave hover:bg-gg-papel hover:text-gg-tinta' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
                @endforeach

                {{-- Solo admin --}}
                @if(Auth::check() && Auth::user()->hasRole('admin'))
                <div class="pt-4 mt-4 border-t border-gg-borde">
                    <p class="px-3 mb-1 text-2xs font-medium text-gg-tinta-suave uppercase tracking-wider">Administración</p>
                    <a href="{{ route('admin.inicio') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-control text-sm text-gg-tinta-suave hover:bg-gg-papel hover:text-gg-tinta transition-colors duration-150">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Modelo predictivo
                    </a>
                </div>
                @endif
            </nav>

            {{-- Usuario / cerrar sesión --}}
            <div class="border-t border-gg-borde p-4 shrink-0">
                @auth
                <p class="text-xs font-medium text-gg-tinta truncate">{{ Auth::user()->name }}</p>
                <p class="text-2xs text-gg-tinta-suave truncate mb-2">{{ Auth::user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-xs text-gg-tinta-suave hover:text-gg-tinta transition-colors duration-150">
                        Cerrar sesión
                    </button>
                </form>
                @endauth
            </div>
        </aside>

        {{-- Overlay para cerrar drawer en móvil --}}
        <div
            class="fixed inset-0 z-20 bg-gg-tinta/30 lg:hidden"
            x-show="navAbierta"
            x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="navAbierta = false"
            aria-hidden="true"
        ></div>

        {{-- Área de contenido --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-auto">

            {{-- Topbar --}}
            <header class="sticky top-0 z-10 h-14 bg-gg-superficie border-b border-gg-borde flex items-center gap-4 px-4 lg:px-6 shrink-0">

                {{-- Botón hamburguesa (solo móvil) --}}
                <button
                    class="lg:hidden p-1.5 -ml-1.5 rounded-control text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel"
                    @click="navAbierta = !navAbierta"
                    :aria-expanded="navAbierta"
                    aria-label="Abrir menú de navegación"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Breadcrumb / título de pantalla --}}
                <div class="flex-1 min-w-0">
                    @isset($encabezado)
                        {{ $encabezado }}
                    @else
                        <h1 class="font-display text-md font-medium text-gg-tinta truncate">
                            {{ $titulo ?? 'Panel' }}
                        </h1>
                    @endisset
                </div>

                {{-- Acciones de topbar opcionales --}}
                @isset($acciones)
                    <div class="shrink-0 flex items-center gap-2">
                        {{ $acciones }}
                    </div>
                @endisset

            </header>

            {{-- Contenido de la página --}}
            <main class="flex-1 px-4 lg:px-6 py-6">
                {{ $slot }}
            </main>

        </div>

    </div>

</body>
</html>
