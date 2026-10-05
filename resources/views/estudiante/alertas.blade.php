<x-layouts.estudiante titulo="Alertas">

    <div class="gg-entrada">

    <div class="mb-8">
        <h1 class="font-display text-3xl font-medium text-gg-tinta">Alertas</h1>
        <p class="text-base text-gg-tinta-suave mt-1">
            Avisos internos cuando tu nivel de riesgo cambia entre una encuesta y otra.
        </p>
    </div>

    @if($alertas->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-14 px-6 gg-flujo-claro overflow-hidden">
            <span class="w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center mb-5">
                <x-icono nombre="alertas" class="w-7 h-7" />
            </span>
            <h2 class="font-display text-xl font-medium text-gg-tinta mb-2">No tienes alertas</h2>
            <p class="text-base text-gg-tinta-suave max-w-xs">
                Aquí aparecerán los avisos cuando tu nivel de riesgo cambie respecto a tu evaluación anterior.
            </p>
        </x-tarjeta>
    @else
        <ul class="space-y-3" role="list">
            @foreach($alertas as $alerta)
            <li>
                <x-tarjeta padding="p-4 sm:p-5" class="flex flex-col sm:flex-row sm:items-start gap-4 relative overflow-hidden {{ $alerta->leida_at ? '' : '!border-[#B9CBBF]' }}">
                    @unless($alerta->leida_at)
                        <span class="absolute left-0 top-0 bottom-0 w-1 bg-gg-primario" aria-hidden="true"></span>
                    @endunless
                    <span class="shrink-0 w-10 h-10 rounded-full inline-flex items-center justify-center
                                 {{ $alerta->leida_at ? 'bg-gg-papel text-gg-tinta-suave' : 'bg-gg-primario-suave text-gg-primario' }}">
                        <x-icono nombre="alertas" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            @unless($alerta->leida_at)
                                <span class="text-xs font-medium text-gg-primario">Nueva</span>
                                <span class="sr-only">No leída</span>
                                <span class="text-gg-borde" aria-hidden="true">·</span>
                            @endunless
                            <time datetime="{{ $alerta->created_at->toDateString() }}" class="text-sm text-gg-tinta-suave">
                                {{ $alerta->created_at->format('d/m/Y') }}
                            </time>
                        </div>
                        <p class="text-base text-gg-tinta leading-relaxed">{{ $alerta->mensaje }}</p>
                    </div>

                    @unless($alerta->leida_at)
                        <form method="POST" action="{{ route('alertas.marcarLeida', $alerta) }}" class="shrink-0 sm:self-center">
                            @csrf
                            @method('PATCH')
                            <x-boton variante="secundario" tipo="submit" tamano="sm" icono="check" class="w-full sm:w-auto">
                                Marcar como leída
                            </x-boton>
                        </form>
                    @endunless
                </x-tarjeta>
            </li>
            @endforeach
        </ul>
    @endif

    </div>

</x-layouts.estudiante>
