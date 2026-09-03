<x-layouts.estudiante titulo="Tu resultado">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">
            Tu resultado
        </h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Clasificación de riesgo digestivo según tus respuestas.
        </p>
    </div>

    <x-tarjeta>
        <x-pista-riesgo
            :categoria="$evaluacion->categoria"
            :prob-bajo="(float) $evaluacion->prob_0"
            :prob-medio="(float) $evaluacion->prob_1"
            :prob-alto="(float) $evaluacion->prob_2"
            :contribuciones="$evaluacion->contribuciones"
            :fecha="$evaluacion->evaluado_at->format('d/m/Y')"
            :codigo-participante="Auth::user()->codigo_participante"
        />
    </x-tarjeta>

    <div class="mt-6 flex justify-center">
        <x-boton variante="secundario" href="{{ route('estudiante.inicio') }}">
            Volver al inicio
        </x-boton>
    </div>

</x-layouts.estudiante>
