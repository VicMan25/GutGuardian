<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titulo ?? config('app.name') }} — GutGuardián</title>

    {{-- Fuentes: Bricolage Grotesque (display), Source Sans 3 (cuerpo), IBM Plex Mono (datos) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="h-full font-sans antialiased bg-gg-papel text-gg-tinta">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-3 focus:left-3
              focus:bg-gg-superficie focus:border focus:border-gg-primario focus:rounded-control
              focus:px-3 focus:py-2 focus:text-sm focus:text-gg-primario">
        Saltar al contenido
    </a>

    <div class="min-h-full lg:grid lg:grid-cols-[minmax(0,42%)_1fr]">

        {{-- Panel de marca — solo escritorio. Aprovecha el ancho sin degradados ni sombras. --}}
        <aside class="hidden lg:flex flex-col justify-between bg-gg-primario text-white px-12 py-14">
            <div>
                <span class="font-display text-2xl font-medium tracking-tight">GutGuardián</span>
                <p class="mt-2 text-sm text-white/80 max-w-xs leading-relaxed">
                    Monitoreo de hábitos alimentarios y salud digestiva · Universidad Mariana
                </p>
            </div>

            <ul class="space-y-6 max-w-sm">
                @foreach([
                    'Registra tus hábitos alimentarios y síntomas digestivos en una encuesta guiada.',
                    'Consulta tu nivel de riesgo digestivo y cómo evoluciona en el tiempo.',
                    'Herramienta de autocuidado y tamizaje académico. No emite diagnósticos médicos.',
                ] as $punto)
                <li class="flex gap-3 text-sm text-white/90 leading-relaxed">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ $punto }}</span>
                </li>
                @endforeach
            </ul>

            <p class="text-2xs text-white/60 max-w-xs leading-relaxed">
                Datos protegidos según la Ley 1581 de 2012. Res. 3100 de 2019 — el aplicativo
                no sustituye la consulta con un profesional de salud.
            </p>
        </aside>

        {{-- Panel de contenido --}}
        <div class="flex flex-col items-center justify-center px-4 py-12 lg:py-16">

            {{-- Marca compacta — visible en móvil y tablet --}}
            <a href="{{ url('/') }}" class="lg:hidden mb-8 flex flex-col items-center gap-2 no-underline">
                <span class="font-display text-xl font-medium text-gg-primario tracking-tight">GutGuardián</span>
                <span class="text-2xs text-gg-tinta-suave text-center leading-tight max-w-[260px]">
                    Monitoreo de hábitos alimentarios · Universidad Mariana
                </span>
            </a>

            {{-- Tarjeta principal --}}
            <main id="contenido" class="w-full max-w-[460px] bg-gg-superficie border border-gg-borde rounded-tarjeta p-8">
                {{ $slot }}
            </main>

            {{-- Pie institucional --}}
            <p class="mt-8 text-2xs text-gg-tinta-suave text-center max-w-sm leading-relaxed">
                Esta herramienta no realiza diagnóstico clínico.&thinsp;
                <span class="whitespace-nowrap">Res. 3100 de 2019.</span>
            </p>

        </div>

    </div>

</body>
</html>
