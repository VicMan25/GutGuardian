<x-layouts.profesional titulo="Estudiantes">

    <x-slot:acciones>
        @can('crear_usuarios')
        <x-boton variante="primario" href="{{ route('panel.usuarios.create') }}" tamano="sm" icono="mas">
            Nuevo estudiante
        </x-boton>
        @endcan
    </x-slot:acciones>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('panel.estudiantes.index') }}" class="relative w-full sm:max-w-sm" role="search">
            <label for="q" class="sr-only">Buscar por nombre, código o programa</label>
            <x-icono nombre="buscar" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-gg-tinta-suave pointer-events-none" />
            <input
                id="q" type="search" name="q" value="{{ $busqueda }}"
                placeholder="Buscar por nombre, código o programa…"
                class="w-full rounded-full border border-gg-borde text-gg-tinta bg-gg-superficie pl-11 pr-4 shadow-elev-1"
            />
        </form>
        @if($busqueda !== '' && $estudiantes->isNotEmpty())
            <p class="text-sm text-gg-tinta-suave">
                Resultados para «{{ $busqueda }}» ·
                <a href="{{ route('panel.estudiantes.index') }}" class="text-gg-primario font-medium hover:underline">Limpiar</a>
            </p>
        @endif
    </div>

    @if($estudiantes->isEmpty())
        <x-tarjeta class="flex flex-col items-center text-center py-14 px-6">
            <span class="w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center mb-5">
                <x-icono nombre="{{ $busqueda !== '' ? 'buscar' : 'estudiantes' }}" class="w-7 h-7" />
            </span>
            <p class="text-base text-gg-tinta-suave">
                @if($busqueda !== '')
                    No hay estudiantes que coincidan con "{{ $busqueda }}".
                @else
                    Aún no hay estudiantes registrados.
                @endif
            </p>
            @if($busqueda !== '')
                <x-boton variante="secundario" tamano="sm" href="{{ route('panel.estudiantes.index') }}" class="mt-5">Limpiar búsqueda</x-boton>
            @endif
        </x-tarjeta>
    @else
        {{-- Móvil: tarjetas apiladas --}}
        <ul class="md:hidden space-y-3" role="list">
            @foreach($estudiantes as $estudiante)
            @php
                $ultimo = $estudiante->diligenciamientos->first();
                $evaluacion = $ultimo?->evaluaciones->first();
            @endphp
            <li>
                <a href="{{ route('panel.estudiantes.show', $estudiante) }}"
                   class="block p-4 bg-gg-superficie border border-gg-borde rounded-tarjeta shadow-elev-1 gg-interactiva">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-base font-medium text-gg-primario truncate">{{ $estudiante->name }}</p>
                            <p class="font-mono text-xs text-gg-tinta-suave mt-0.5">{{ $estudiante->codigo_participante }}</p>
                        </div>
                        <x-insignia-riesgo :categoria="$evaluacion?->categoria" tamano="sm" />
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 text-sm text-gg-tinta-suave">
                        <span class="truncate">{{ $estudiante->perfil?->programa?->nombre ?? '—' }}</span>
                        @if($estudiante->activo)
                            <span class="shrink-0">Activo</span>
                        @else
                            <span class="shrink-0 text-gg-riesgo-alto font-medium">Desactivado</span>
                        @endif
                    </div>
                </a>
            </li>
            @endforeach
        </ul>

        {{-- Desktop: tabla --}}
        <x-tarjeta padding="p-0" class="hidden md:block overflow-hidden">
            <div class="overflow-x-auto">
            <table class="gg-tabla">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Código</th>
                        <th scope="col">Programa</th>
                        <th scope="col">Riesgo actual</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Abrir ficha</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estudiantes as $estudiante)
                    @php
                        $ultimo = $estudiante->diligenciamientos->first();
                        $evaluacion = $ultimo?->evaluaciones->first();
                    @endphp
                    <tr class="group">
                        <td>
                            <a href="{{ route('panel.estudiantes.show', $estudiante) }}" class="font-medium text-gg-primario hover:underline">
                                {{ $estudiante->name }}
                            </a>
                        </td>
                        <td class="font-mono text-xs text-gg-tinta-suave">{{ $estudiante->codigo_participante }}</td>
                        <td class="text-gg-tinta-suave">{{ $estudiante->perfil?->programa?->nombre ?? '—' }}</td>
                        <td>
                            <x-insignia-riesgo :categoria="$evaluacion?->categoria" tamano="sm" />
                        </td>
                        <td>
                            @if($estudiante->activo)
                                <span class="inline-flex items-center gap-1.5 text-sm text-gg-tinta-suave">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gg-riesgo-bajo" aria-hidden="true"></span>Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-sm text-gg-riesgo-alto font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gg-riesgo-alto" aria-hidden="true"></span>Desactivado
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('panel.estudiantes.show', $estudiante) }}"
                               class="inline-flex w-8 h-8 items-center justify-center rounded-full text-gg-tinta-suave group-hover:text-gg-primario group-hover:bg-gg-primario-suave transition-colors"
                               aria-label="Abrir ficha de {{ $estudiante->name }}" tabindex="-1">
                                <x-icono nombre="flecha" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </x-tarjeta>

        <div class="mt-5">{{ $estudiantes->links() }}</div>
    @endif

</x-layouts.profesional>
