<x-layouts.estudiante titulo="Tu resultado">

    @php $esEstudiante = Auth::user()->hasRole('estudiante'); @endphp

    <div class="gg-entrada">

    <div class="mb-6">
        @if($esEstudiante)
        <a href="{{ route('historial.show') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gg-tinta-suave hover:text-gg-tinta transition-colors mb-3">
            <x-icono nombre="atras" class="w-4 h-4" />
            Historial
        </a>
        @endif
        <h1 class="font-display text-3xl font-medium text-gg-tinta">
            Tu resultado
        </h1>
        <p class="text-base text-gg-tinta-suave mt-1">
            Clasificación de riesgo digestivo según tus respuestas.
        </p>
    </div>

    <x-tarjeta padding="p-6 sm:p-8" class="rounded-bloque shadow-elev-2">
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

    <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-center gap-3">
        <x-boton variante="secundario" href="{{ $esEstudiante ? route('estudiante.inicio') : url('/') }}" icono="atras">
            Volver al inicio
        </x-boton>
        @if($esEstudiante)
        <x-boton variante="primario" href="{{ route('seguimiento.show') }}" icono="seguimiento">
            Ver mi evolución
        </x-boton>
        @endif
    </div>

    </div>

</x-layouts.estudiante>
