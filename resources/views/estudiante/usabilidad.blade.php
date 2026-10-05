<x-layouts.estudiante titulo="Evaluar GutGuardián">

    <div class="mb-6">
        <h1 class="font-display text-3xl font-medium text-gg-tinta">Evalúa tu experiencia</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Diez afirmaciones sobre qué tan fácil fue usar GutGuardián. Es voluntario, toma unos dos minutos
            y tus respuestas no afectan tu resultado de riesgo.
        </p>
    </div>

    @if($evaluacion)
        <x-tarjeta class="text-center py-10">
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">Gracias por tu evaluación</h2>
            <p class="text-sm text-gg-tinta-suave max-w-sm mx-auto mb-6">
                Registramos tus respuestas el {{ $evaluacion->created_at->format('d/m/Y') }}.
                Nos ayudan a validar y mejorar el aplicativo.
            </p>
            <x-boton variante="secundario" href="{{ route('estudiante.inicio') }}" tamano="sm">Volver al inicio</x-boton>
        </x-tarjeta>
    @else
        @if($errors->any())
            <x-alerta tipo="error" titulo="Faltan respuestas" class="mb-6">
                Responde todas las afirmaciones antes de enviar.
            </x-alerta>
        @endif

        <form method="POST" action="{{ route('usabilidad.store') }}" class="space-y-4">
            @csrf

            @foreach($items as $item => $enunciado)
            <x-tarjeta>
                <fieldset>
                    <legend class="text-sm text-gg-tinta mb-3">
                        <span class="font-mono text-gg-tinta-suave mr-1">{{ $item }}.</span> {{ $enunciado }}
                    </legend>
                    <div class="grid grid-cols-5 gap-1.5" role="radiogroup">
                        @foreach($escala as $valor => $etiqueta)
                        <label class="flex flex-col items-center gap-1 text-center cursor-pointer">
                            <input type="radio" name="respuestas[{{ $item }}]" value="{{ $valor }}" required
                                   @checked((int) old("respuestas.$item") === $valor)
                                   class="peer sr-only">
                            <span class="w-full py-2 rounded-control border border-gg-borde text-sm font-mono text-gg-tinta
                                         peer-checked:bg-gg-primario-suave peer-checked:border-gg-primario peer-checked:text-gg-primario
                                         peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-gg-primario">
                                {{ $valor }}
                            </span>
                            <span class="text-xs leading-tight text-gg-tinta-suave hidden sm:block">{{ $etiqueta }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-xs text-gg-tinta-suave mt-1.5 sm:hidden" aria-hidden="true">
                        <span>{{ $escala[1] }}</span><span>{{ $escala[5] }}</span>
                    </div>
                    @error("respuestas.$item")
                        <p class="text-xs text-gg-riesgo-alto mt-2" role="alert">{{ $message }}</p>
                    @enderror
                </fieldset>
            </x-tarjeta>
            @endforeach

            <x-tarjeta>
                <label for="comentario" class="block text-sm font-medium text-gg-tinta mb-1.5">Comentario (opcional)</label>
                <textarea id="comentario" name="comentario" rows="3" maxlength="1000"
                          class="w-full rounded-control border border-gg-borde text-sm focus:outline-none focus:border-gg-primario"
                          placeholder="¿Algo que te resultó confuso o que mejorarías?">{{ old('comentario') }}</textarea>
                <p class="text-xs text-gg-tinta-suave mt-1">No escribas datos de salud ni datos personales aquí.</p>
            </x-tarjeta>

            <x-boton tipo="submit">Enviar evaluación</x-boton>
        </form>
    @endif

</x-layouts.estudiante>
