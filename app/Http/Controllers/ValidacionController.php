<?php

namespace App\Http\Controllers;

use App\Modules\Analitica\Models\VersionModelo;
use App\Modules\Encuestas\Models\EvaluacionUsabilidad;
use App\Modules\Reportes\Services\UsabilidadService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Indicadores de validación del aplicativo (objetivo 1.3.2.4, Sprint 6):
 * usabilidad percibida (SUS), tiempo de diligenciamiento, tasa de errores de
 * ingreso de datos y estado del modelo predictivo activo.
 */
class ValidacionController extends Controller
{
    public function __construct(private readonly UsabilidadService $usabilidad) {}

    public function index(): View
    {
        return view('admin.validacion.index', [
            'sus' => $this->usabilidad->resumenSus(),
            'interaccion' => $this->usabilidad->metricasInteraccion(),
            'items' => UsabilidadService::ITEMS,
            'modelo' => VersionModelo::where('activo', true)->first(),
        ]);
    }

    /**
     * Respuestas SUS anonimizadas para el análisis de la monografía: sin
     * nombre, correo ni código de participante (Ley 1581, minimización).
     */
    public function exportarSus(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['registro', 'fecha', ...array_map(fn ($i) => "item_$i", array_keys(UsabilidadService::ITEMS)), 'puntaje_sus', 'aceptabilidad']);

            EvaluacionUsabilidad::orderBy('id')->get()->each(function (EvaluacionUsabilidad $e, int $i) use ($salida) {
                $respuestas = array_map(fn ($item) => $e->respuestas[$item] ?? $e->respuestas[(string) $item] ?? '', array_keys(UsabilidadService::ITEMS));
                fputcsv($salida, [$i + 1, $e->created_at->format('Y-m-d'), ...$respuestas, $e->puntaje, $this->usabilidad->aceptabilidad($e->puntaje)]);
            });

            fclose($salida);
        }, 'sus-gutguardian.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
