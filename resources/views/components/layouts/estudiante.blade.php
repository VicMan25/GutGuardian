@props([
    // 'estrecho' (640 px): encuesta, resultado y formularios — lectura enfocada.
    // 'amplio' (1040 px): inicio y seguimiento — tableros con varias columnas.
    'ancho'  => 'estrecho',
    'titulo' => null,
])
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F6F7F4">

    <title>{{ $titulo ?? 'Encuesta' }} — GutGuardián</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-full font-sans antialiased gg-fondo text-gg-tinta">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3
              focus:bg-gg-superficie focus:border focus:border-gg-primario focus:rounded-control
              focus:px-3 focus:py-2 focus:text-sm focus:text-gg-primario">
        Saltar al contenido
    </a>

    @php
        $anchoContenido = $ancho === 'amplio' ? 'max-w-estudiante-amplio' : 'max-w-estudiante';

        // Navegación principal. Las 4 primeras van en la barra superior (md+) y
        // junto a Perfil en la barra inferior (móvil). "Perfil" y "Mis datos"
        // viven además en el menú de cuenta, siempre visible.
        $navPrincipal = [
            ['ruta' => 'estudiante.inicio', 'etiqueta' => 'Inicio',      'icono' => 'inicio'],
            ['ruta' => 'historial.show',    'etiqueta' => 'Historial',   'icono' => 'historial'],
            ['ruta' => 'seguimiento.show',  'etiqueta' => 'Seguimiento', 'icono' => 'seguimiento'],
            ['ruta' => 'alertas.index',     'etiqueta' => 'Alertas',     'icono' => 'alertas'],
        ];
        $navCuenta = [
            ['ruta' => 'perfil.edit',    'etiqueta' => 'Perfil',    'icono' => 'perfil'],
            ['ruta' => 'mis-datos.show', 'etiqueta' => 'Mis datos', 'icono' => 'datos'],
        ];

        // /resultado también lo abren profesional_salud y admin (DiligenciamientoPolicy):
        // para ellos no se muestran las rutas del área del estudiante, que les
        // responderían 403, sino un regreso a su panel ("/" redirige según el rol).
        $esEstudiante = Auth::check() && Auth::user()->hasRole('estudiante');
        if (! $esEstudiante) {
            $navPrincipal = [];
            $navCuenta = [];
        }

        $alertasNoLeidas = $esEstudiante ? Auth::user()->alertas()->whereNull('leida_at')->count() : 0;
        $iniciales = Auth::check()
            ? collect(explode(' ', trim(Auth::user()->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('')
            : '';
    @endphp

    {{-- ============================================================
         Cabecera — translúcida y fija. Logo · navegación · cuenta.
    ============================================================ --}}
    <header class="sticky top-0 z-30 bg-gg-papel/85 backdrop-blur-md border-b border-gg-borde/80 pt-[env(safe-area-inset-top)]">
        <div class="max-w-estudiante-amplio mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

            <x-marca tamano="sm" :href="$esEstudiante ? route('estudiante.inicio') : url('/')" class="shrink-0" />

            @if(Auth::check() && ! $esEstudiante)
                <x-boton variante="secundario" tamano="sm" :href="url('/')" icono="atras" class="ml-auto">
                    Volver al panel
                </x-boton>
            @endif

            {{--
                Navbar dinámico: siempre visible (incluso al confirmar/ver el
                resultado, donde antes no había forma de volver al panel de
                inicio salvo el logo). Resalta la sección activa con
                request()->routeIs(), igual que layouts/profesional.
            --}}
            @auth
            @if($esEstudiante)
            <nav aria-label="Navegación principal" class="hidden md:flex items-center gap-1 p-1 rounded-full bg-gg-superficie/80 border border-gg-borde shadow-elev-1">
                @foreach($navPrincipal as $item)
                    @php $activo = request()->routeIs($item['ruta']); @endphp
                    <a href="{{ route($item['ruta']) }}"
                       @if($activo) aria-current="page" @endif
                       class="relative inline-flex items-center gap-2 h-9 px-3.5 rounded-full text-sm transition-colors duration-150
                              {{ $activo ? 'bg-gg-primario text-white font-medium shadow-boton' : 'text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel-hondo' }}">
                        <x-icono :nombre="$item['icono']" class="w-4 h-4" />
                        {{ $item['etiqueta'] }}
                        @if($item['ruta'] === 'alertas.index' && $alertasNoLeidas > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-2xs font-medium
                                         {{ $activo ? 'bg-white text-gg-primario' : 'bg-gg-primario text-white' }}">
                                {{ $alertasNoLeidas }}<span class="sr-only"> sin leer</span>
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
            @endif

            {{-- Menú de cuenta --}}
            <div class="relative shrink-0" x-data="{ abierto: false }" @keydown.escape.window="abierto = false" @click.outside="abierto = false">
                <button type="button"
                        class="flex items-center gap-2.5 h-10 pl-1 pr-1 sm:pr-3 rounded-full border border-gg-borde bg-gg-superficie hover:border-[#C7D0C9] shadow-elev-1 transition-colors"
                        @click="abierto = !abierto"
                        :aria-expanded="abierto"
                        aria-haspopup="menu"
                        aria-label="Menú de cuenta de {{ Auth::user()->name }}">
                    <span class="w-8 h-8 rounded-full bg-gg-primario-suave text-gg-primario text-sm font-medium inline-flex items-center justify-center" aria-hidden="true">
                        {{ $iniciales }}
                    </span>
                    <span class="hidden sm:block text-sm text-gg-tinta truncate max-w-[140px]">{{ Auth::user()->name }}</span>
                    <svg class="hidden sm:block w-4 h-4 text-gg-tinta-suave transition-transform duration-200" :class="abierto && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="abierto" x-cloak
                     x-transition:enter="transition duration-150 ease-out"
                     x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition duration-100 ease-in"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute right-0 mt-2 w-64 origin-top-right rounded-tarjeta bg-gg-superficie border border-gg-borde shadow-elev-3 p-2"
                     role="menu">
                    <div class="px-3 py-2.5 mb-1 border-b border-gg-borde">
                        <p class="text-sm font-medium text-gg-tinta truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gg-tinta-suave truncate">{{ Auth::user()->email }}</p>
                    </div>
                    @foreach($navCuenta as $item)
                        @php $activo = request()->routeIs($item['ruta']); @endphp
                        <a href="{{ route($item['ruta']) }}" role="menuitem"
                           @if($activo) aria-current="page" @endif
                           class="flex items-center gap-3 px-3 h-11 rounded-control text-sm transition-colors
                                  {{ $activo ? 'bg-gg-primario-suave text-gg-primario font-medium' : 'text-gg-tinta hover:bg-gg-papel' }}">
                            <x-icono :nombre="$item['icono']" class="w-[18px] h-[18px] text-gg-tinta-suave" />
                            {{ $item['etiqueta'] }}
                        </a>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}" class="mt-1 pt-1 border-t border-gg-borde">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="w-full flex items-center gap-3 px-3 h-11 rounded-control text-sm text-gg-tinta hover:bg-gg-papel transition-colors">
                            <x-icono nombre="salir" class="w-[18px] h-[18px] text-gg-tinta-suave" />
                            Salir
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </div>

        {{-- Progreso de la encuesta: fila propia bajo la cabecera --}}
        @if(isset($progreso))
            <div class="max-w-estudiante mx-auto px-4 sm:px-6 pb-3">
                {{ $progreso }}
            </div>
        @endif
    </header>

    {{-- Contenido principal --}}
    <main id="contenido" class="{{ $anchoContenido }} mx-auto px-4 sm:px-6 pt-6 sm:pt-10 pb-8 md:pb-10">
        {{ $slot }}
    </main>

    {{-- Pie: aviso legal siempre visible --}}
    <footer class="{{ $anchoContenido }} mx-auto px-4 sm:px-6 pb-28 md:pb-10">
        <p class="text-xs text-gg-tinta-suave text-center">
            GutGuardián no emite diagnóstico clínico.
            Res. 3100 de 2019 · Universidad Mariana, Pasto.
        </p>
    </footer>

    {{-- ============================================================
         Barra de pestañas inferior — solo móvil. Objetivos táctiles de
         56 px, al alcance del pulgar, respetando el área segura del iPhone.
    ============================================================ --}}
    @if($esEstudiante)
    <nav aria-label="Navegación principal móvil"
         class="md:hidden fixed bottom-0 inset-x-0 z-30 bg-gg-superficie/95 backdrop-blur-md border-t border-gg-borde
                pb-[env(safe-area-inset-bottom)]">
        <ul class="grid grid-cols-5">
            @foreach(array_merge($navPrincipal, [$navCuenta[0]]) as $item)
                @php $activo = request()->routeIs($item['ruta']); @endphp
                <li>
                    <a href="{{ route($item['ruta']) }}"
                       @if($activo) aria-current="page" @endif
                       class="relative flex flex-col items-center justify-center gap-1 h-16 text-xs transition-colors
                              {{ $activo ? 'text-gg-primario font-medium' : 'text-gg-tinta-suave active:text-gg-tinta' }}">
                        @if($activo)
                            <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-gg-primario" aria-hidden="true"></span>
                        @endif
                        <span class="relative inline-flex items-center justify-center w-12 h-7 rounded-full transition-colors {{ $activo ? 'bg-gg-primario-suave' : '' }}">
                            <x-icono :nombre="$item['icono']" class="w-[22px] h-[22px]" />
                            @if($item['ruta'] === 'alertas.index' && $alertasNoLeidas > 0)
                                <span class="absolute -top-1 right-1 min-w-[1.125rem] h-[1.125rem] px-1 rounded-full bg-gg-primario text-white text-[11px] leading-[1.125rem] text-center font-medium">
                                    {{ $alertasNoLeidas }}<span class="sr-only"> sin leer</span>
                                </span>
                            @endif
                        </span>
                        {{ $item['etiqueta'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
    @endif

</body>
</html>
