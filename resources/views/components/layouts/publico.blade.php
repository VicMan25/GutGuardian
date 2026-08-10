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

    <div class="min-h-full flex flex-col items-center justify-center px-4 py-12">

        {{-- Marca --}}
        <a href="{{ url('/') }}" class="mb-8 flex flex-col items-center gap-2 no-underline">
            <span class="font-display text-xl font-medium text-gg-primario tracking-tight">
                GutGuardián
            </span>
            <span class="text-2xs text-gg-tinta-suave text-center leading-tight max-w-[260px]">
                Monitoreo de hábitos alimentarios · Universidad Mariana
            </span>
        </a>

        {{-- Tarjeta principal --}}
        <div class="w-full max-w-[480px] bg-gg-superficie border border-gg-borde rounded-tarjeta p-8">
            {{ $slot }}
        </div>

        {{-- Pie institucional --}}
        <p class="mt-8 text-2xs text-gg-tinta-suave text-center max-w-sm leading-relaxed">
            Esta herramienta no realiza diagnóstico clínico.&thinsp;
            <span class="whitespace-nowrap">Res. 3100 de 2019.</span>
        </p>

    </div>

</body>
</html>
