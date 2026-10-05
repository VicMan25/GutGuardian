@php
    $metricas = $version->metricas ?? [];
    $mapa = $version->mapa_variables ?? [];
    $etiquetas = collect($mapa['variables'] ?? [])->map(fn ($v) => $v['etiqueta'] ?? null);
    $categorias = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
    $vif = $metricas['vif'] ?? [];
    $mensajes = [
        'version-registrada' => 'Versión registrada. Queda inactiva hasta que la actives.',
        'version-registrada-activa' => 'Versión registrada y activada. Las evaluaciones nuevas usarán esta versión.',
        'version-activada' => 'Versión activada. Las evaluaciones nuevas usarán esta versión.',
    ];
@endphp

<x-layouts.profesional :titulo="'Modelo v'.$version->version">

    @unless($version->activo)
    <x-slot:acciones>
        <form method="POST" action="{{ route('admin.modelos.activar', $version) }}">
            @csrf
            <x-boton tipo="submit" tamano="sm">Activar esta versión</x-boton>
        </form>
    </x-slot:acciones>
    @endunless

    <a href="{{ route('admin.modelos.index') }}"
       class="inline-flex items-center gap-1.5 text-xs text-gg-tinta-suave hover:text-gg-tinta mb-4">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a versiones
    </a>

    @if(session('status'))
        <x-alerta tipo="exito" class="mb-6">{{ $mensajes[session('status')] ?? session('status') }}</x-alerta>
    @endif
    @if($errors->has('version'))
        <x-alerta tipo="error" titulo="No se pudo activar la versión" class="mb-6">
            @foreach($errors->get('version') as $mensaje) <p>{{ $mensaje }}</p> @endforeach
        </x-alerta>
    @endif

    <x-tarjeta class="mb-6">
        <div class="flex flex-wrap items-start gap-x-8 gap-y-3 text-sm">
            <div class="min-w-0">
                <p class="text-xs text-gg-tinta-suave">Nombre</p>
                <p class="text-gg-tinta">{{ $version->nombre }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Estado</p>
                <p class="{{ $version->activo ? 'text-gg-primario font-medium' : 'text-gg-tinta' }}">{{ $version->activo ? 'Activa' : 'Inactiva' }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Entrenado</p>
                <p class="text-gg-tinta">{{ $version->entrenado_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gg-tinta-suave">Evaluaciones producidas</p>
                <p class="font-mono text-gg-tinta">{{ $version->evaluaciones_count }}</p>
            </div>
        </div>
        @if(! empty($mapa['limitaciones']))
            <x-alerta tipo="aviso" titulo="Limitaciones declaradas" class="mt-4">{{ $mapa['limitaciones'] }}</x-alerta>
        @endif
    </x-tarjeta>

    {{-- Métricas de desempeño (TRIPOD+AI: reportar desempeño del modelo) --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['Exactitud (prueba)', $metricas['exactitud_test'] ?? null],
            ['Exactitud (VC 5 pliegues)', $metricas['validacion_cruzada']['exactitud_promedio'] ?? null],
            ['AUC multiclase (macro)', $metricas['auc_macro_ovr_test'] ?? null],
            ['VIF máximo', $vif ? max($vif) : null],
            ['Registros (entr. / prueba)', isset($metricas['n_train']) ? $metricas['n_train'].' / '.$metricas['n_test'] : null],
        ] as [$etiqueta, $valor])
        <x-tarjeta padding="p-4">
            <p class="font-mono text-xl text-gg-tinta">{{ is_numeric($valor) ? number_format($valor, 2) : ($valor ?? '—') }}</p>
            <p class="text-xs text-gg-tinta-suave mt-1">{{ $etiqueta }}</p>
        </x-tarjeta>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Coeficientes como razón de probabilidades frente a la categoría base --}}
        <x-tarjeta padding="p-0" class="lg:col-span-2 overflow-x-auto">
            <div class="px-5 pt-5 pb-3">
                <h2 class="font-display text-xl font-medium text-gg-tinta">Coeficientes</h2>
                <p class="text-xs text-gg-tinta-suave mt-1">
                    β y razón de probabilidades (OR = exp β) de cada categoría frente a la base ({{ $categorias[$mapa['categoria_base'] ?? 0] }}).
                </p>
            </div>
            <table class="w-full text-sm">
                <caption class="sr-only">Coeficientes del modelo por categoría</caption>
                <thead>
                    <tr class="border-y border-gg-borde text-left text-sm text-gg-tinta-suave bg-gg-papel">
                        <th scope="col" class="px-4 py-2 font-medium">Variable</th>
                        @foreach($version->coeficientes as $categoria => $coef)
                            <th scope="col" class="px-4 py-2 font-medium text-right">β {{ $categorias[$categoria] ?? $categoria }}</th>
                            <th scope="col" class="px-4 py-2 font-medium text-right">OR</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="font-mono text-xs">
                    <tr class="border-b border-gg-borde">
                        <th scope="row" class="px-4 py-2 font-sans font-normal text-left text-gg-tinta-suave">Intercepto</th>
                        @foreach($version->coeficientes as $coef)
                            <td class="px-4 py-2 text-right">{{ number_format($coef['intercepto'], 3) }}</td>
                            <td class="px-4 py-2 text-right">—</td>
                        @endforeach
                    </tr>
                    @foreach($mapa['orden_variables'] ?? [] as $variable)
                    <tr class="border-b border-gg-borde last:border-0">
                        <th scope="row" class="px-4 py-2 font-sans font-normal text-left text-gg-tinta">{{ $etiquetas[$variable] ?? $variable }}</th>
                        @foreach($version->coeficientes as $coef)
                            @php $beta = $coef['beta'][$variable] ?? null; @endphp
                            <td class="px-4 py-2 text-right">{{ $beta === null ? '—' : number_format($beta, 3) }}</td>
                            <td class="px-4 py-2 text-right">{{ $beta === null ? '—' : number_format(exp($beta), 2) }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-tarjeta>

        {{-- Matriz de confusión --}}
        <div class="space-y-4">
            @if(! empty($metricas['matriz_confusion_test']))
            <x-tarjeta>
                <h2 class="font-display text-xl font-medium text-gg-tinta mb-1">Matriz de confusión (prueba)</h2>
                <p class="text-xs text-gg-tinta-suave mb-3">Filas: categoría real · columnas: categoría predicha.</p>
                <table class="w-full text-xs">
                    <caption class="sr-only">Matriz de confusión del conjunto de prueba</caption>
                    <thead>
                        <tr class="text-sm text-gg-tinta-suave">
                            <th scope="col" class="py-1 text-left font-medium">Real</th>
                            @foreach($metricas['matriz_confusion_test'] as $i => $fila)
                                <th scope="col" class="py-1 text-right font-medium">{{ $i }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="font-mono">
                        @foreach($metricas['matriz_confusion_test'] as $i => $fila)
                        <tr class="border-t border-gg-borde">
                            <th scope="row" class="py-1.5 text-left font-sans font-normal text-gg-tinta-suave">{{ $categorias[$i] ?? $i }}</th>
                            @foreach($fila as $j => $n)
                                <td class="py-1.5 text-right {{ $i === $j ? 'text-gg-primario font-medium' : 'text-gg-tinta' }}">{{ $n }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tarjeta>
            @endif

            @if(! empty($metricas['limitacion_epv']))
            <x-tarjeta>
                <h2 class="font-display text-xl font-medium text-gg-tinta mb-1">Eventos por variable</h2>
                <p class="text-xs text-gg-tinta-suave">{{ $metricas['limitacion_epv'] }}</p>
            </x-tarjeta>
            @endif
        </div>

    </div>

</x-layouts.profesional>
