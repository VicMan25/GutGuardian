<x-layouts.profesional :titulo="$estudiante->name">

    <x-slot:acciones>
        @can('editar_usuarios')
        <x-boton variante="secundario" href="{{ route('panel.usuarios.edit', $estudiante) }}" tamano="sm" icono="editar">Editar</x-boton>
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

    <a href="{{ route('panel.estudiantes.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gg-tinta-suave hover:text-gg-tinta mb-5 transition-colors">
        <x-icono nombre="atras" class="w-4 h-4" />
        Volver a estudiantes
    </a>

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">
            @switch(session('status'))
                @case('estudiante-creado') Estudiante creado correctamente. @break
                @case('estudiante-actualizado') Datos actualizados correctamente. @break
                @default {{ session('status') }}
            @endswitch
        </x-alerta>
    @endif

    @php
        $ultimaEvaluacion = $diligenciamientos->first()?->evaluaciones->first();
        $iniciales = collect(explode(' ', trim($estudiante->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('');
    @endphp

    <x-tarjeta class="mb-6" padding="p-0">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5 sm:p-6 border-b border-gg-borde">
            <span class="shrink-0 w-14 h-14 rounded-full bg-gg-primario-suave text-gg-primario font-display text-xl font-medium inline-flex items-center justify-center" aria-hidden="true">{{ $iniciales }}</span>
            <div class="min-w-0 flex-1">
                <p class="font-display text-2xl font-medium text-gg-tinta truncate">{{ $estudiante->name }}</p>
                <p class="text-sm text-gg-tinta-suave">{{ $diligenciamientos->count() }} {{ $diligenciamientos->count() === 1 ? 'encuesta completada' : 'encuestas completadas' }}</p>
            </div>
            @if($ultimaEvaluacion)
                <div class="sm:text-right">
                    <p class="text-xs text-gg-tinta-suave mb-1">Nivel más reciente</p>
                    <x-insignia-riesgo :categoria="$ultimaEvaluacion->categoria" />
                </div>
            @endif
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-x-6 gap-y-4 p-5 sm:p-6 text-base">
            <div>
                <p class="text-xs text-gg-tinta-suave">Código</p>
                <p class="font-mono text-gg-tinta">{{ $estudiante->codigo_participante }}</p>
            </div>
            <div class="col-span-2 lg:col-span-1 min-w-0">
                <p class="text-xs text-gg-tinta-suave">Correo</p>
                <p class="text-gg-tinta truncate">{{ $estudiante->email }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Programa</p>
                <p class="text-gg-tinta">{{ $estudiante->perfil?->programa?->nombre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Semestre</p>
                <p class="text-gg-tinta">{{ $estudiante->perfil?->semestre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Estado</p>
                <p class="{{ $estudiante->activo ? 'text-gg-tinta' : 'text-gg-riesgo-alto font-medium' }}">
                    {{ $estudiante->activo ? 'Activo' : 'Desactivado' }}
                </p>
            </div>
        </div>
    </x-tarjeta>

    @if($riesgo)
        <x-tarjeta class="mb-6">
            <h2 class="font-display text-xl font-medium text-gg-tinta">Evolución del nivel de riesgo</h2>
            <p class="text-sm text-gg-tinta-suave mt-0.5 mb-5">Probabilidad de cada nivel en cada encuesta.</p>
            <div class="h-64 sm:h-72">
                <canvas
                    x-data
                    x-init="new Chart($el, {
                        type: 'line',
                        data: {
                            labels: {{ Js::from($riesgo['etiquetas']) }},
                            datasets: [
                                { label: 'Riesgo bajo', data: {{ Js::from($riesgo['bajo']) }}, borderColor: '#3E7D64', backgroundColor: '#3E7D64', tension: 0.35 },
                                { label: 'Riesgo medio', data: {{ Js::from($riesgo['medio']) }}, borderColor: '#C08A2E', backgroundColor: '#C08A2E', tension: 0.35 },
                                { label: 'Riesgo alto', data: {{ Js::from($riesgo['alto']) }}, borderColor: '#9C4A32', backgroundColor: '#9C4A32', tension: 0.35 },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, padding: 16, font: { size: 13 } } } },
                        },
                    })"
                    role="img"
                    aria-label="Gráfica de evolución de las probabilidades de riesgo bajo, medio y alto"
                ></canvas>
            </div>
        </x-tarjeta>
    @endif

    <x-tarjeta>
        <h2 class="font-display text-xl font-medium text-gg-tinta mb-4">Historial de encuestas</h2>

        @if($diligenciamientos->isEmpty())
            <p class="text-base text-gg-tinta-suave">Este estudiante aún no tiene encuestas completadas.</p>
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
                       class="group flex items-center justify-between gap-4 px-4 min-h-[56px] rounded-control border border-gg-borde hover:border-[#C7D0C9] hover:bg-gg-papel transition-colors duration-150">
                        <time datetime="{{ $diligenciamiento->completado_at->toDateString() }}" class="text-base text-gg-tinta">
                            {{ $diligenciamiento->completado_at->format('d/m/Y') }}
                        </time>
                        <span class="flex items-center gap-3">
                            @if($evaluacion)
                                <x-insignia-riesgo :categoria="$evaluacion->categoria" tamano="sm" />
                            @endif
                            <x-icono nombre="flecha" class="w-4 h-4 text-gg-tinta-suave transition-transform duration-200 group-hover:translate-x-0.5" />
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    {{-- Resolución 3100 de 2019 — obligatorio junto a todo resultado de riesgo,
         también en la vista del profesional. --}}
    @if($riesgo || $diligenciamientos->contains(fn ($d) => $d->evaluaciones->isNotEmpty()))
        <div class="mt-6">
            <x-aviso-no-diagnostico />
        </div>
    @endif

</x-layouts.profesional>
