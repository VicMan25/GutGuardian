<x-layouts.profesional titulo="Modelo predictivo">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Versiones del modelo predictivo</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Solo una versión puede estar activa. Las evaluaciones ya registradas conservan la versión que las produjo.
        </p>
    </div>

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">{{ session('status') }}</x-alerta>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        <x-tarjeta padding="p-0" class="lg:col-span-2 overflow-x-auto">
            @if($versiones->isEmpty())
                <p class="p-5 text-sm text-gg-tinta-suave">
                    Todavía no hay versiones registradas. Carga el archivo que produce <code class="font-mono">ml/exportar_modelo.py</code>.
                </p>
            @else
                <table class="w-full text-sm">
                    <caption class="sr-only">Versiones registradas del modelo predictivo</caption>
                    <thead>
                        <tr class="border-b border-gg-borde text-left text-2xs text-gg-tinta-suave">
                            <th scope="col" class="px-4 py-3 font-medium">Versión</th>
                            <th scope="col" class="px-4 py-3 font-medium">Entrenado</th>
                            <th scope="col" class="px-4 py-3 font-medium">Exactitud</th>
                            <th scope="col" class="px-4 py-3 font-medium">AUC</th>
                            <th scope="col" class="px-4 py-3 font-medium">Evaluaciones</th>
                            <th scope="col" class="px-4 py-3 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versiones as $version)
                        <tr class="border-b border-gg-borde last:border-0">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.modelos.show', $version) }}" class="font-mono text-gg-primario hover:underline">
                                    v{{ $version->version }}
                                </a>
                                <p class="text-2xs text-gg-tinta-suave truncate max-w-[260px]">{{ $version->nombre }}</p>
                            </td>
                            <td class="px-4 py-3 text-gg-tinta-suave">{{ $version->entrenado_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono">{{ isset($version->metricas['exactitud_test']) ? number_format($version->metricas['exactitud_test'], 2) : '—' }}</td>
                            <td class="px-4 py-3 font-mono">{{ isset($version->metricas['auc_macro_ovr_test']) ? number_format($version->metricas['auc_macro_ovr_test'], 2) : '—' }}</td>
                            <td class="px-4 py-3 font-mono">{{ $version->evaluaciones_count }}</td>
                            <td class="px-4 py-3">
                                @if($version->activo)
                                    <span class="inline-flex px-2 py-0.5 rounded-control text-2xs font-medium bg-gg-primario-suave text-gg-primario">Activa</span>
                                @else
                                    <span class="text-2xs text-gg-tinta-suave">Inactiva</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-tarjeta>

        <x-tarjeta>
            <h2 class="text-sm font-medium text-gg-tinta mb-1">Registrar una versión</h2>
            <p class="text-2xs text-gg-tinta-suave mb-4">
                Archivo JSON generado fuera de la aplicación por el pipeline de entrenamiento (<code class="font-mono">ml/</code>).
                Se valida antes de guardarse.
            </p>

            <form method="POST" action="{{ route('admin.modelos.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="archivo" class="block text-xs font-medium text-gg-tinta mb-1.5">Archivo del modelo</label>
                    <input id="archivo" name="archivo" type="file" accept=".json,application/json" required
                           aria-describedby="archivo-error"
                           class="block w-full text-xs text-gg-tinta-suave file:mr-3 file:px-3 file:py-1.5 file:rounded-control
                                  file:border file:border-gg-primario file:bg-transparent file:text-gg-primario file:text-xs">
                    @error('archivo')
                        <div id="archivo-error" class="mt-2 text-xs text-gg-riesgo-alto space-y-1">
                            @foreach($errors->get('archivo') as $mensaje)
                                <p>{{ $mensaje }}</p>
                            @endforeach
                        </div>
                    @enderror
                </div>
                <label class="flex items-start gap-2 text-xs text-gg-tinta">
                    <input type="checkbox" name="activar" value="1" class="mt-0.5 rounded border-gg-borde text-gg-primario focus:ring-gg-primario">
                    Activarla al registrarla
                </label>
                <x-boton tipo="submit" tamano="sm">Registrar versión</x-boton>
            </form>
        </x-tarjeta>

    </div>

</x-layouts.profesional>
