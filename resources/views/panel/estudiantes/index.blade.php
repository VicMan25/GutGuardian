<x-layouts.profesional titulo="Estudiantes">

    <x-slot:acciones>
        @can('crear_usuarios')
        <x-boton variante="primario" href="{{ route('panel.usuarios.create') }}" tamano="sm">
            Nuevo estudiante
        </x-boton>
        @endcan
    </x-slot:acciones>

    <form method="GET" action="{{ route('panel.estudiantes.index') }}" class="mb-6 max-w-sm">
        <label for="q" class="sr-only">Buscar por nombre, código o programa</label>
        <input
            id="q" type="search" name="q" value="{{ $busqueda }}"
            placeholder="Buscar por nombre, código o programa…"
            class="w-full rounded-control border border-gg-borde text-sm text-gg-tinta bg-gg-superficie px-3 py-2
                   focus:outline-none focus:border-gg-primario"
        />
    </form>

    @if($estudiantes->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-12 px-6">
            <p class="text-sm text-gg-tinta-suave">
                @if($busqueda !== '')
                    No hay estudiantes que coincidan con "{{ $busqueda }}".
                @else
                    Aún no hay estudiantes registrados.
                @endif
            </p>
        </x-tarjeta>
    @else
        @php
        $etiquetasCategoria = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
        $bgHex   = ['#3E7D64', '#C08A2E', '#9C4A32'];
        $bgSuave = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];
        @endphp
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="text-left text-2xs text-gg-tinta-suave uppercase tracking-wide border-b border-gg-borde">
                    <th class="py-2 pr-4 font-medium">Nombre</th>
                    <th class="py-2 pr-4 font-medium">Código</th>
                    <th class="py-2 pr-4 font-medium">Programa</th>
                    <th class="py-2 pr-4 font-medium">Riesgo actual</th>
                    <th class="py-2 pr-4 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estudiantes as $estudiante)
                @php
                    $ultimo = $estudiante->diligenciamientos->first();
                    $evaluacion = $ultimo?->evaluaciones->first();
                @endphp
                <tr class="border-b border-gg-borde hover:bg-gg-superficie">
                    <td class="py-2.5 pr-4">
                        <a href="{{ route('panel.estudiantes.show', $estudiante) }}" class="font-medium text-gg-primario hover:underline">
                            {{ $estudiante->name }}
                        </a>
                    </td>
                    <td class="py-2.5 pr-4 font-mono text-2xs text-gg-tinta-suave">{{ $estudiante->codigo_participante }}</td>
                    <td class="py-2.5 pr-4 text-gg-tinta-suave">{{ $estudiante->perfil?->programa?->nombre ?? '—' }}</td>
                    <td class="py-2.5 pr-4">
                        @if($evaluacion)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-control"
                                  style="color: {{ $bgHex[$evaluacion->categoria] }}; background-color: {{ $bgSuave[$evaluacion->categoria] }};">
                                {{ $etiquetasCategoria[$evaluacion->categoria] }}
                            </span>
                        @else
                            <span class="text-2xs text-gg-tinta-suave">Sin evaluar</span>
                        @endif
                    </td>
                    <td class="py-2.5 pr-4">
                        @if($estudiante->activo)
                            <span class="text-2xs text-gg-tinta-suave">Activo</span>
                        @else
                            <span class="text-2xs text-gg-riesgo-alto font-medium">Desactivado</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="mt-4">{{ $estudiantes->links() }}</div>
    @endif

</x-layouts.profesional>
