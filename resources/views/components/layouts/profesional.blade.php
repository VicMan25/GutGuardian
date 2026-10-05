<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#174A3B">

    <title>{{ $titulo ?? 'Panel' }} — GutGuardián</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="h-full font-sans antialiased gg-fondo text-gg-tinta" x-data="{ navAbierta: false }" @keydown.escape.window="navAbierta = false">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3
              focus:bg-gg-superficie focus:border focus:border-gg-primario focus:rounded-control
              focus:px-3 focus:py-2 focus:text-sm focus:text-gg-primario">
        Saltar al contenido
    </a>

    @php
        $navItems = [
            ['label' => 'Panel', 'route' => 'panel.inicio', 'icon' => 'panel'],
        ];
        if (Auth::check() && Auth::user()->can('consultar_estudiantes')) {
            $navItems[] = ['label' => 'Estudiantes', 'route' => 'panel.estudiantes.index', 'activo' => 'panel.estudiantes.*', 'icon' => 'estudiantes'];
        }
        if (Auth::check() && Auth::user()->can('generar_reportes')) {
            $navItems[] = ['label' => 'Reportes', 'route' => 'panel.reportes.index', 'activo' => 'panel.reportes.*', 'icon' => 'reportes'];
        }

        $navAdmin = [
            ['label' => 'Modelo predictivo', 'route' => 'admin.modelos.index',    'activo' => 'admin.modelos.*',    'icon' => 'modelo'],
            ['label' => 'Validación',        'route' => 'admin.validacion.index', 'activo' => 'admin.validacion.*', 'icon' => 'validacion'],
            ['label' => 'Auditoría',         'route' => 'admin.auditoria.index',  'activo' => 'admin.auditoria.*',  'icon' => 'auditoria'],
        ];

        $claseEnlace = fn (bool $activo) => 'group relative flex items-center gap-3 h-11 px-3 rounded-control text-sm transition-colors duration-150 '
            .($activo
                ? 'bg-white/[0.12] text-white font-medium'
                : 'text-white/70 hover:bg-white/[0.06] hover:text-white');

        $iniciales = Auth::check()
            ? collect(explode(' ', trim(Auth::user()->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('')
            : '';
    @endphp

    <div class="flex h-full">

        {{-- Sidebar: fijo en desktop, drawer en móvil. Superficie de marca. --}}
        <aside
            class="gg-marca fixed inset-y-0 left-0 z-40 w-[272px] flex flex-col
                   transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]
                   lg:translate-x-0 lg:static lg:inset-auto lg:h-full"
            :class="navAbierta ? 'translate-x-0 shadow-elev-3' : '-translate-x-full lg:translate-x-0'"
            aria-label="Navegación principal"
        >
            {{-- Marca --}}
            <div class="h-[72px] flex items-center justify-between gap-2 px-5 shrink-0">
                <x-marca tono="claro" subtitulo="Panel institucional" :href="route('panel.inicio')" />
                <button class="lg:hidden w-10 h-10 inline-flex items-center justify-center rounded-control text-white/70 hover:text-white hover:bg-white/10"
                        @click="navAbierta = false" aria-label="Cerrar menú de navegación">
                    <x-icono nombre="cerrar" />
                </button>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 overflow-y-auto pt-4 pb-4 px-3" aria-label="Menú de sección">
                <p class="px-3 mb-2 text-xs font-medium text-white/50 tracking-wide">General</p>
                <div class="space-y-1">
                    @foreach($navItems as $item)
                    @php $activo = request()->routeIs($item['activo'] ?? $item['route']); @endphp
                    <a href="{{ route($item['route']) }}" @if($activo) aria-current="page" @endif class="{{ $claseEnlace($activo) }}">
                        @if($activo)
                            <span class="absolute -left-3 top-2 bottom-2 w-[3px] rounded-r-full bg-gg-acento" aria-hidden="true"></span>
                        @endif
                        <x-icono :nombre="$item['icon']" class="w-[18px] h-[18px] {{ $activo ? 'text-gg-acento' : '' }}" />
                        {{ $item['label'] }}
                    </a>
                    @endforeach
                </div>

                {{-- Solo admin --}}
                @if(Auth::check() && Auth::user()->hasRole('admin'))
                <div class="pt-5 mt-5 border-t border-white/10">
                    <p class="px-3 mb-2 text-xs font-medium text-white/50 tracking-wide">Administración</p>
                    <div class="space-y-1">
                        @foreach($navAdmin as $item)
                        @php $activo = request()->routeIs($item['activo']); @endphp
                        <a href="{{ route($item['route']) }}" @if($activo) aria-current="page" @endif class="{{ $claseEnlace($activo) }}">
                            @if($activo)
                                <span class="absolute -left-3 top-2 bottom-2 w-[3px] rounded-r-full bg-gg-acento" aria-hidden="true"></span>
                            @endif
                            <x-icono :nombre="$item['icon']" class="w-[18px] h-[18px] {{ $activo ? 'text-gg-acento' : '' }}" />
                            {{ $item['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </nav>

            {{-- Usuario / cerrar sesión --}}
            @auth
            <div class="p-3 shrink-0">
                <div class="flex items-center gap-3 p-3 rounded-tarjeta bg-white/[0.06] border border-white/10">
                    <span class="w-9 h-9 shrink-0 rounded-full bg-gg-acento text-gg-primario-noche text-sm font-medium inline-flex items-center justify-center" aria-hidden="true">
                        {{ $iniciales }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-white/60 truncate">{{ Auth::user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-9 h-9 inline-flex items-center justify-center rounded-control text-white/70 hover:text-white hover:bg-white/10 transition-colors duration-150"
                                aria-label="Cerrar sesión" title="Cerrar sesión">
                            <x-icono nombre="salir" class="w-[18px] h-[18px]" />
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </aside>

        {{-- Overlay para cerrar drawer en móvil --}}
        <div
            class="fixed inset-0 z-30 bg-gg-primario-noche/40 backdrop-blur-[2px] lg:hidden"
            x-show="navAbierta"
            x-cloak
            x-transition:enter="transition-opacity duration-200"
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
            <header class="sticky top-0 z-20 min-h-16 bg-gg-papel/85 backdrop-blur-md border-b border-gg-borde/80 shrink-0">
                <div class="mx-auto w-full max-w-[1180px] min-h-16 flex flex-wrap items-center gap-x-4 gap-y-2 px-4 lg:px-8 py-3">

                    {{-- Botón hamburguesa (solo móvil) --}}
                    <button
                        class="lg:hidden w-10 h-10 -ml-2 inline-flex items-center justify-center rounded-control text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel-hondo"
                        @click="navAbierta = !navAbierta"
                        :aria-expanded="navAbierta"
                        aria-label="Abrir menú de navegación"
                    >
                        <x-icono nombre="menu" />
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
                        <div class="shrink-0 flex flex-wrap items-center gap-2">
                            {{ $acciones }}
                        </div>
                    @endisset
                </div>
            </header>

            {{-- Contenido de la página — ancho acotado para longitud de línea legible --}}
            <main id="contenido" class="flex-1 px-4 lg:px-8 py-6 lg:py-8">
                <div class="mx-auto w-full max-w-[1180px] gg-entrada">
                    {{ $slot }}
                </div>
            </main>

        </div>

    </div>

</body>
</html>
