<x-layouts.estudiante titulo="Confirmar encuesta">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Confirmar y enviar</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Revisa que hayas completado todas las secciones antes de enviar tu encuesta.
        </p>
    </div>

    @if(session('error'))
        <div class="mb-4">
            <x-alerta tipo="error">{{ session('error') }}</x-alerta>
        </div>
    @endif

    <x-tarjeta class="mb-6">
        @if($todoCompleto)
            <p class="text-sm text-gg-tinta">
                Has respondido todas las preguntas del instrumento. Una vez envíes la encuesta,
                no podrás modificar tus respuestas.
            </p>
        @else
            <p class="text-sm text-gg-tinta">
                Aún tienes preguntas sin responder.
                <a href="{{ route('encuesta.iniciar') }}" class="text-gg-primario font-medium underline">
                    Volver a la encuesta
                </a>
                para completarlas.
            </p>
        @endif
    </x-tarjeta>

    @if($todoCompleto)
    <form method="POST" action="{{ route('encuesta.finalizar', $diligenciamiento) }}">
        @csrf
        <x-boton variante="primario" tipo="submit">
            Enviar encuesta
        </x-boton>
    </form>
    @endif

    <div class="mt-6">
        <x-aviso-no-diagnostico />
    </div>

</x-layouts.estudiante>
