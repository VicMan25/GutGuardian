<x-layouts.estudiante titulo="Confirmar encuesta">

    <div class="gg-entrada">

    <div class="mb-6">
        <p class="gg-rotulo">Último paso</p>
        <h1 class="mt-1 font-display text-3xl font-medium text-gg-tinta">Confirmar y enviar</h1>
        <p class="text-base text-gg-tinta-suave mt-2">
            Revisa que hayas completado todas las secciones antes de enviar tu encuesta.
        </p>
    </div>

    @if(session('error'))
        <div class="mb-4">
            <x-alerta tipo="error">{{ session('error') }}</x-alerta>
        </div>
    @endif

    <x-tarjeta class="mb-6 flex gap-4" padding="p-5 sm:p-6">
        @if($todoCompleto)
            <span class="shrink-0 w-11 h-11 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center">
                <x-icono nombre="check" />
            </span>
            <div>
                <p class="text-base font-medium text-gg-tinta">Todo listo</p>
                <p class="text-base text-gg-tinta-suave mt-1 leading-relaxed">
                    Has respondido todas las preguntas del instrumento. Una vez envíes la encuesta,
                    no podrás modificar tus respuestas.
                </p>
            </div>
        @else
            <span class="shrink-0 w-11 h-11 rounded-full bg-[#FBF3E2] text-[#8A6116] inline-flex items-center justify-center">
                <x-icono nombre="encuesta" />
            </span>
            <p class="text-base text-gg-tinta pt-2.5">
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
        <x-boton variante="primario" tipo="submit" tamano="lg" icono-final="flecha" class="w-full sm:w-auto">
            Enviar encuesta
        </x-boton>
    </form>
    @endif

    <div class="mt-8">
        <x-aviso-no-diagnostico />
    </div>

    </div>

</x-layouts.estudiante>
