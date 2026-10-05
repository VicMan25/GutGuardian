@php
    $fmt = fn ($v, $dec = 1) => $v === null ? '—' : number_format($v, $dec, ',', '.');
@endphp

<x-layouts.profesional titulo="Validación">

    <x-slot:acciones>
        @if($sus['n'] > 0)
            <x-boton variante="secundario" tamano="sm" href="{{ route('admin.validacion.exportarSus') }}">Exportar SUS (CSV)</x-boton>
        @endif
    </x-slot:acciones>

    <div class="mb-6">
        <h1 class="font-display text-3xl font-medium text-gg-tinta">Indicadores de validación</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Evidencia para la prueba piloto: usabilidad percibida, tiempo de diligenciamiento, errores de ingreso y modelo activo.
        </p>
    </div>

    {{-- SUS --}}
    <h2 class="font-display text-xl font-medium text-gg-tinta mb-3">Usabilidad percibida (System Usability Scale)</h2>
    @if($sus['n'] === 0)
        <x-tarjeta class="mb-6">
            <p class="text-sm text-gg-tinta-suave">
                Todavía ningún estudiante ha respondido el SUS. La invitación aparece en su inicio después de completar la primera encuesta.
            </p>
        </x-tarjeta>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            @foreach([
                ['Respuestas', $sus['n'], 0],
                ['Puntaje medio (0–100)', $sus['media'], 1],
                ['Mediana', $sus['mediana'], 1],
                ['Desviación estándar', $sus['desviacion'], 1],
            ] as [$etiqueta, $valor, $dec])
            <x-tarjeta padding="p-4">
                <p class="font-display text-3xl font-medium text-gg-tinta">{{ $fmt($valor, $dec) }}</p>
                <p class="text-xs text-gg-tinta-suave mt-1">{{ $etiqueta }}</p>
            </x-tarjeta>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 items-start">
            <x-tarjeta>
                <h3 class="text-xs font-medium text-gg-tinta mb-3">Aceptabilidad (Bangor et al., 2008)</h3>
                <dl class="space-y-2 text-sm">
                    @foreach($sus['aceptabilidad'] as $rango => $n)
                    <div class="flex justify-between">
                        <dt class="text-gg-tinta-suave">{{ $rango }}</dt>
                        <dd class="font-mono text-gg-tinta">{{ $n }} · {{ $fmt($n / $sus['n'] * 100) }}%</dd>
                    </div>
                    @endforeach
                </dl>
                <p class="text-xs text-gg-tinta-suave mt-3">≥ 70 aceptable · 50–69,9 marginal · &lt; 50 no aceptable.</p>
            </x-tarjeta>

            <x-tarjeta padding="p-0" class="lg:col-span-2 overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="text-left px-5 pt-4 pb-2 text-xs font-medium text-gg-tinta">Media por afirmación (1 a 5)</caption>
                    <tbody>
                        @foreach($items as $item => $enunciado)
                        <tr class="border-t border-gg-borde">
                            <th scope="row" class="px-5 py-2 text-left font-normal text-xs text-gg-tinta">
                                <span class="font-mono text-gg-tinta-suave">{{ $item }}.</span> {{ $enunciado }}
                            </th>
                            <td class="px-5 py-2 text-right font-mono text-xs">{{ $fmt($sus['por_item'][$item], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="px-5 py-3 text-xs text-gg-tinta-suave border-t border-gg-borde">
                    Afirmaciones impares: más alto es mejor. Pares: más bajo es mejor.
                </p>
            </x-tarjeta>
        </div>
    @endif

    {{-- Interacción --}}
    <h2 class="font-display text-xl font-medium text-gg-tinta mb-3">Interacción con la encuesta</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <x-tarjeta>
            <p class="text-xs text-gg-tinta-suave">Tiempo de diligenciamiento (mediana)</p>
            <p class="font-display text-3xl font-medium text-gg-tinta mt-1">
                {{ $fmt($interaccion['tiempo']['mediana']) }} <span class="text-sm font-sans text-gg-tinta-suave">min</span>
            </p>
            <p class="text-xs text-gg-tinta-suave mt-2">
                Rango intercuartílico {{ $fmt($interaccion['tiempo']['p25']) }}–{{ $fmt($interaccion['tiempo']['p75']) }} min
                · {{ $interaccion['tiempo']['n'] }} encuestas completadas.
                Incluye pausas: la encuesta se puede reanudar.
            </p>
        </x-tarjeta>
        <x-tarjeta>
            <p class="text-xs text-gg-tinta-suave">Tasa de errores al ingresar datos</p>
            <p class="font-display text-3xl font-medium text-gg-tinta mt-1">
                {{ $fmt($interaccion['errores']['tasa']) }} <span class="text-sm font-sans text-gg-tinta-suave">%</span>
            </p>
            <p class="text-xs text-gg-tinta-suave mt-2">
                {{ $interaccion['errores']['con_error'] }} de {{ $interaccion['errores']['envios'] }} envíos de sección rechazados por validación.
            </p>
        </x-tarjeta>
    </div>

    {{-- Modelo --}}
    <h2 class="font-display text-xl font-medium text-gg-tinta mb-3">Modelo predictivo activo</h2>
    <x-tarjeta>
        @if($modelo)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gg-tinta"><span class="font-mono">v{{ $modelo->version }}</span> — {{ $modelo->nombre }}</p>
                    <p class="text-xs text-gg-tinta-suave mt-1">
                        Exactitud {{ $fmt($modelo->metricas['exactitud_test'] ?? null, 2) }}
                        · AUC {{ $fmt($modelo->metricas['auc_macro_ovr_test'] ?? null, 2) }}
                    </p>
                </div>
                <x-boton variante="secundario" tamano="sm" href="{{ route('admin.modelos.show', $modelo) }}">Ver detalle</x-boton>
            </div>
            @if(! empty($modelo->mapa_variables['limitaciones']))
                <x-alerta tipo="aviso" class="mt-4">{{ $modelo->mapa_variables['limitaciones'] }}</x-alerta>
            @endif
        @else
            <p class="text-sm text-gg-tinta-suave">No hay una versión activa. Regístrala en «Modelo predictivo».</p>
        @endif
    </x-tarjeta>

</x-layouts.profesional>
