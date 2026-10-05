<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#174A3B">

    <title>{{ $titulo ?? config('app.name') }} — GutGuardián</title>

    {{-- Fuentes: Bricolage Grotesque (display), Source Sans 3 (cuerpo), IBM Plex Mono (datos) --}}
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

    <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,46%)_1fr]">

        {{-- ============================================================
             Panel de marca — escritorio. Verde profundo con el patrón de
             flujo, propuesta de valor y un adelanto de la pista de riesgo
             (la pieza central del producto) como elemento visual.
        ============================================================ --}}
        <aside class="gg-marca hidden lg:flex flex-col justify-between overflow-hidden px-12 xl:px-16 py-12 lg:sticky lg:top-0 lg:h-screen">

            <x-marca tono="claro" tamano="lg" subtitulo="Universidad Mariana · Pasto" :href="url('/')" />

            <div class="max-w-md">
                <p class="gg-rotulo !text-gg-acento mb-4">Autocuidado digestivo</p>
                <p class="font-display text-4xl font-medium text-white">
                    Tus hábitos cuentan una historia. Aprende a leerla.
                </p>
                <p class="mt-5 text-md text-white/80 leading-relaxed">
                    Registra tu alimentación y tus síntomas, conoce tu nivel de riesgo digestivo
                    y observa cómo evoluciona con el tiempo.
                </p>

                {{-- Adelanto ilustrativo de la pista de riesgo (decorativo) --}}
                <div class="mt-10 rounded-tarjeta bg-white/[0.06] border border-white/10 p-5 backdrop-blur-sm" aria-hidden="true">
                    <div class="flex items-center justify-between text-xs text-white/70 mb-3">
                        <span>Así se ve tu resultado</span>
                        <span class="font-mono">3 niveles</span>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <div class="h-2 rounded-full bg-[#7FB89D]"></div>
                        <div class="h-2 rounded-full bg-[#E0B467]/50"></div>
                        <div class="h-2 rounded-full bg-[#C98A73]/40"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5 mt-2 text-xs text-white/75">
                        <span>Bajo</span><span>Medio</span><span>Alto</span>
                    </div>
                </div>
            </div>

            <ul class="grid grid-cols-3 gap-6 max-w-lg text-sm text-white/80">
                @foreach([
                    ['icono' => 'encuesta',    'texto' => 'Encuesta guiada de 20 preguntas'],
                    ['icono' => 'seguimiento', 'texto' => 'Evolución en el tiempo'],
                    ['icono' => 'datos',       'texto' => 'Datos protegidos · Ley 1581/2012'],
                ] as $punto)
                <li class="flex flex-col gap-2.5">
                    <span class="w-9 h-9 rounded-control bg-white/10 inline-flex items-center justify-center text-gg-acento">
                        <x-icono :nombre="$punto['icono']" class="w-[18px] h-[18px]" />
                    </span>
                    <span class="leading-snug">{{ $punto['texto'] }}</span>
                </li>
                @endforeach
            </ul>
        </aside>

        {{-- ============================================================
             Panel de contenido
        ============================================================ --}}
        <div class="flex flex-col min-h-screen">

            {{-- Cabecera de marca compacta — móvil y tablet: banda verde con
                 la marca en lugar de un título suelto sobre el papel. --}}
            <header class="gg-marca lg:hidden px-5 pt-[max(1.5rem,env(safe-area-inset-top))] pb-16 sm:pb-20">
                <div class="max-w-[460px] mx-auto">
                    <x-marca tono="claro" subtitulo="Universidad Mariana · Pasto" :href="url('/')" />
                    <p class="mt-6 font-display text-2xl sm:text-3xl font-medium text-white max-w-sm">
                        Tus hábitos cuentan una historia.
                    </p>
                </div>
            </header>

            <div class="flex-1 flex flex-col items-center justify-start lg:justify-center px-4 sm:px-6 -mt-10 sm:-mt-12 lg:mt-0 pb-10 lg:py-16">

                {{-- Tarjeta principal --}}
                <main id="contenido"
                      class="w-full max-w-[460px] bg-gg-superficie border border-gg-borde rounded-bloque shadow-elev-3 lg:shadow-elev-2
                             p-6 sm:p-9 animate-gg-entrada">
                    {{ $slot }}
                </main>

                {{-- Pie institucional --}}
                <p class="mt-8 text-xs text-gg-tinta-suave text-center max-w-sm leading-relaxed">
                    Esta herramienta no realiza diagnóstico clínico.&thinsp;
                    <span class="whitespace-nowrap">Res. 3100 de 2019.</span>
                </p>
            </div>

        </div>

    </div>

</body>
</html>
