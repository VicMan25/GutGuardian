@php
    $eventos = [
        'consulta_ficha' => 'Consulta de ficha',
        'consulta_resultado' => 'Consulta de resultado',
        'consulta_reporte' => 'Consulta de reporte',
        'exportacion_reporte' => 'Exportación de reporte',
        'registro_version' => 'Registro de versión',
        'activacion_version' => 'Activación de versión',
    ];
@endphp

<x-layouts.profesional titulo="Auditoría">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Registro de auditoría</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Quién accedió a información clínica y cuándo (Ley 1581 de 2012). Los registros no se pueden modificar desde la aplicación.
        </p>
    </div>

    <x-tarjeta class="mb-4">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="registro" class="block text-xs font-medium text-gg-tinta mb-1.5">Registro</label>
                <select id="registro" name="registro"
                        class="rounded-control border-gg-borde text-sm focus:border-gg-primario focus:ring-gg-primario">
                    @foreach($registros as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($registro === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="desde" class="block text-xs font-medium text-gg-tinta mb-1.5">Desde</label>
                <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}"
                       class="rounded-control border-gg-borde text-sm focus:border-gg-primario focus:ring-gg-primario">
            </div>
            <div>
                <label for="hasta" class="block text-xs font-medium text-gg-tinta mb-1.5">Hasta</label>
                <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}"
                       class="rounded-control border-gg-borde text-sm focus:border-gg-primario focus:ring-gg-primario">
            </div>
            <x-boton tipo="submit" variante="secundario" tamano="sm">Filtrar</x-boton>
        </form>
        @if($errors->any())
            <div class="mt-3 text-xs text-gg-riesgo-alto">
                @foreach($errors->all() as $mensaje) <p>{{ $mensaje }}</p> @endforeach
            </div>
        @endif
    </x-tarjeta>

    <x-tarjeta padding="p-0" class="overflow-x-auto">
        @if($actividades->isEmpty())
            <p class="p-5 text-sm text-gg-tinta-suave">No hay registros para los filtros seleccionados.</p>
        @else
            <table class="w-full text-sm">
                <caption class="sr-only">Registros de auditoría</caption>
                <thead>
                    <tr class="border-b border-gg-borde text-left text-2xs text-gg-tinta-suave">
                        <th scope="col" class="px-4 py-3 font-medium">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-medium">Usuario</th>
                        <th scope="col" class="px-4 py-3 font-medium">Acción</th>
                        <th scope="col" class="px-4 py-3 font-medium">Sobre</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actividades as $actividad)
                    <tr class="border-b border-gg-borde last:border-0 align-top">
                        <td class="px-4 py-3 font-mono text-xs text-gg-tinta-suave whitespace-nowrap">{{ $actividad->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <p class="text-gg-tinta">{{ $actividad->causer?->name ?? 'Sistema' }}</p>
                            <p class="text-2xs text-gg-tinta-suave">{{ $actividad->causer?->getRoleNames()->first() }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gg-tinta">{{ $eventos[$actividad->event] ?? $actividad->event }}</p>
                            <p class="text-2xs text-gg-tinta-suave">{{ $actividad->description }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gg-tinta-suave">
                            @php $props = $actividad->properties; @endphp
                            @if($props->has('codigo_participante'))
                                Estudiante <span class="font-mono text-gg-tinta">{{ $props['codigo_participante'] }}</span>
                            @elseif($props->has('diligenciamiento_id'))
                                Encuesta <span class="font-mono text-gg-tinta">#{{ $props['diligenciamiento_id'] }}</span>
                            @elseif($props->has('version'))
                                Versión <span class="font-mono text-gg-tinta">v{{ $props['version'] }}</span>
                                @if($props->get('version_anterior')) (antes v{{ $props['version_anterior'] }}) @endif
                            @elseif($props->has('accion'))
                                {{ collect($props['filtros'] ?? [])->map(fn ($v, $k) => "$k: $v")->implode(' · ') ?: 'Sin filtros' }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-tarjeta>

    <div class="mt-4">{{ $actividades->links() }}</div>

</x-layouts.profesional>
