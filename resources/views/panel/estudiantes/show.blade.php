<x-layouts.profesional :titulo="$estudiante->name">

    <x-slot:acciones>
        @can('editar_usuarios')
        <x-boton variante="secundario" href="{{ route('panel.usuarios.edit', $estudiante) }}" tamano="sm">Editar</x-boton>
        @endcan
        @can('desactivar_usuarios')
        <form method="POST" action="{{ route('panel.usuarios.alternarActivo', $estudiante) }}">
            @csrf
            <x-boton variante="{{ $estudiante->activo ? 'fantasma' : 'primario' }}" tipo="submit" tamano="sm">
                {{ $estudiante->activo ? 'Desactivar' : 'Reactivar' }}
            </x-boton>
        </form>
        @endcan
    </x-slot:acciones>

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">
            @switch(session('status'))
                @case('estudiante-creado') Estudiante creado correctamente. @break
                @case('estudiante-actualizado') Datos actualizados correctamente. @break
                @default {{ session('status') }}
            @endswitch
        </x-alerta>
    @endif

    <x-tarjeta class="mb-6">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <div>
                <p class="text-2xs text-gg-tinta-suave">Código</p>
                <p class="font-mono text-gg-tinta">{{ $estudiante->codigo_participante }}</p>
            </div>
            <div>
                <p class="text-2xs text-gg-tinta-suave">Correo</p>
                <p class="text-gg-tinta">{{ $estudiante->email }}</p>
            </div>
            <div>
                <p class="text-2xs text-gg-tinta-suave">Programa</p>
                <p class="text-gg-tinta">{{ $estudiante->perfil?->programa?->nombre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-2xs text-gg-tinta-suave">Semestre</p>
                <p class="text-gg-tinta">{{ $estudiante->perfil?->semestre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-2xs text-gg-tinta-suave">Estado</p>
                <p class="{{ $estudiante->activo ? 'text-gg-tinta' : 'text-gg-riesgo-alto font-medium' }}">
                    {{ $estudiante->activo ? 'Activo' : 'Desactivado' }}
                </p>
            </div>
        </div>
    </x-tarjeta>

    @if($riesgo)
        <x-tarjeta class="mb-6">
            <h2 class="text-sm font-medium text-gg-tinta mb-4">Evolución del nivel de riesgo</h2>
            <div class="h-64">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @json($riesgo['etiquetas']),
                            datasets: [
                                { label: 'Riesgo bajo', data: @json($riesgo['bajo']), borderColor: '#3E7D64', backgroundColor: '#3E7D64', tension: 0.25 },
                                { label: 'Riesgo medio', data: @json($riesgo['medio']), borderColor: '#C08A2E', backgroundColor: '#C08A2E', tension: 0.25 },
                                { label: 'Riesgo alto', data: @json($riesgo['alto']), borderColor: '#9C4A32', backgroundColor: '#9C4A32', tension: 0.25 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de evolución de las probabilidades de riesgo bajo, medio y alto"
                ></canvas>
            </div>
        </x-tarjeta>
    @endif

    <x-tarjeta>
        <h2 class="text-sm font-medium text-gg-tinta mb-4">Historial de encuestas</h2>

        @if($diligenciamientos->isEmpty())
            <p class="text-sm text-gg-tinta-suave">Este estudiante aún no tiene encuestas completadas.</p>
        @else
            @php
            $etiquetasCategoria = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
            $bgHex   = ['#3E7D64', '#C08A2E', '#9C4A32'];
            $bgSuave = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];
            @endphp
            <ul class="space-y-2" role="list">
                @foreach($diligenciamientos as $diligenciamiento)
                @php $evaluacion = $diligenciamiento->evaluaciones->first(); @endphp
                <li>
                    <a href="{{ route('resultado.show', $diligenciamiento) }}"
                       class="flex items-center justify-between gap-4 p-3 rounded-control border border-gg-borde hover:bg-gg-papel transition-colors duration-100">
                        <time datetime="{{ $diligenciamiento->completado_at->toDateString() }}" class="text-sm text-gg-tinta">
                            {{ $diligenciamiento->completado_at->format('d/m/Y') }}
                        </time>
                        @if($evaluacion)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-control"
                                  style="color: {{ $bgHex[$evaluacion->categoria] }}; background-color: {{ $bgSuave[$evaluacion->categoria] }};">
                                {{ $etiquetasCategoria[$evaluacion->categoria] }}
                            </span>
                        @endif
                    </a>
                </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

</x-layouts.profesional>
