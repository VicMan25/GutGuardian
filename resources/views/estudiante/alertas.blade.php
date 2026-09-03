<x-layouts.estudiante titulo="Alertas">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Alertas</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Avisos internos cuando tu nivel de riesgo cambia entre una encuesta y otra.
        </p>
    </div>

    @if($alertas->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">No tienes alertas</h2>
            <p class="text-sm text-gg-tinta-suave max-w-xs">
                Aquí aparecerán los avisos cuando tu nivel de riesgo cambie respecto a tu evaluación anterior.
            </p>
        </x-tarjeta>
    @else
        <ul class="space-y-3" role="list">
            @foreach($alertas as $alerta)
            <li>
                <x-tarjeta class="flex items-start justify-between gap-4 {{ $alerta->leida_at ? '' : 'border-gg-primario' }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            @unless($alerta->leida_at)
                                <span class="w-2 h-2 rounded-full bg-gg-primario shrink-0" aria-hidden="true"></span>
                                <span class="sr-only">No leída</span>
                            @endunless
                            <time datetime="{{ $alerta->created_at->toDateString() }}" class="text-2xs text-gg-tinta-suave">
                                {{ $alerta->created_at->format('d/m/Y') }}
                            </time>
                        </div>
                        <p class="text-sm text-gg-tinta">{{ $alerta->mensaje }}</p>
                    </div>

                    @unless($alerta->leida_at)
                        <form method="POST" action="{{ route('alertas.marcarLeida', $alerta) }}" class="shrink-0">
                            @csrf
                            @method('PATCH')
                            <x-boton variante="secundario" tipo="submit" tamano="sm">
                                Marcar como leída
                            </x-boton>
                        </form>
                    @endunless
                </x-tarjeta>
            </li>
            @endforeach
        </ul>
    @endif

</x-layouts.estudiante>
