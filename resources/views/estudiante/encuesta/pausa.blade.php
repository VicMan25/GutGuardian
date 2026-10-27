<x-layouts.estudiante titulo="Listo por hoy">

    @php
        $yaDisponible = $sugeridaDesde === null || $sugeridaDesde->lte(now());
    @endphp

    <div class="gg-entrada space-y-6">

    <section class="gg-marca rounded-bloque overflow-hidden shadow-elev-3 p-6 sm:p-10">
        <span class="w-12 h-12 rounded-full bg-gg-acento text-gg-primario-noche inline-flex items-center justify-center">
            <x-icono nombre="check" />
        </span>
        <h1 class="mt-5 font-display text-3xl sm:text-4xl font-medium text-white">
            ¡Listo por hoy!
        </h1>
        <p class="mt-3 text-base text-white/80 max-w-md leading-relaxed">
            Guardamos tus respuestas del día {{ $completadas }}.
            @if($yaDisponible)
                El día {{ $siguiente }} ya está disponible.
            @else
                El día {{ $siguiente }} estará disponible desde mañana.
            @endif
            Tu nivel de riesgo se calcula al completar los {{ $totalJornadas }} días.
        </p>

        {{-- Avance del ciclo --}}
        <ol class="mt-8 grid gap-2" style="grid-template-columns: repeat({{ $totalJornadas }}, minmax(0, 1fr));"
            aria-label="Avance de la encuesta">
            @for($d = 1; $d <= $totalJornadas; $d++)
                <li>
                    <div class="h-1.5 rounded-full {{ $d <= $completadas ? 'bg-gg-acento' : 'bg-white/20' }}" aria-hidden="true"></div>
                    <p class="mt-2 text-sm {{ $d <= $completadas ? 'text-white' : 'text-white/60' }}">
                        Día {{ $d }}
                        <span class="sr-only">{{ $d <= $completadas ? 'completado' : 'pendiente' }}</span>
                    </p>
                </li>
            @endfor
        </ol>
    </section>

    <x-tarjeta padding="p-5 sm:p-6">
        <p class="text-base font-medium text-gg-tinta">¿Tienes unos minutos más?</p>
        <p class="text-sm text-gg-tinta-suave mt-1 leading-relaxed">
            Puedes seguir ahora o volver cuando quieras: la encuesta no vence y tus respuestas no se pierden.
            @if(Auth::user()->recibir_recordatorios)
                Si no vuelves hoy, te enviaremos un recordatorio a tu correo.
            @endif
        </p>
        <div class="mt-5 flex flex-wrap gap-2">
            <x-boton variante="primario" href="{{ route('encuesta.jornada', [$diligenciamiento, $siguiente]) }}" icono-final="flecha">
                Seguir con el día {{ $siguiente }}
            </x-boton>
            <x-boton variante="secundario" href="{{ route('estudiante.inicio') }}" icono="inicio">
                Volver al inicio
            </x-boton>
        </div>
    </x-tarjeta>

    <x-aviso-no-diagnostico />

    </div>

</x-layouts.estudiante>
