<?php

namespace App\Http\Controllers;

use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Analitica\Services\ExplicabilidadService;
use App\Modules\Analitica\Services\PredictorService;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Panel\Services\AlertaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function __construct(
        private readonly PredictorService $predictor,
        private readonly ExplicabilidadService $explicabilidad,
        private readonly AlertaService $alertas,
    ) {}

    public function show(Diligenciamiento $diligenciamiento): View|RedirectResponse
    {
        $this->authorize('view', $diligenciamiento);

        if ($diligenciamiento->estado !== 'completado') {
            return back()->with('error', 'Debes completar la encuesta antes de ver tu resultado.');
        }

        $evaluacion = $diligenciamiento->evaluaciones()->latest('evaluado_at')->first()
            ?? $this->evaluar($diligenciamiento);

        return view('estudiante.resultado', [
            'evaluacion' => $evaluacion,
            'diligenciamiento' => $diligenciamiento,
        ]);
    }

    /**
     * Orquesta predicción + explicabilidad y persiste el resultado.
     * version_modelo_id queda siempre registrado (CLAUDE.md §5, trazabilidad TRIPOD+AI).
     */
    private function evaluar(Diligenciamiento $diligenciamiento): EvaluacionRiesgo
    {
        $version = $this->predictor->versionActiva();
        $prediccion = $this->predictor->predecir($diligenciamiento);
        $contribuciones = $this->explicabilidad->explicar($prediccion, $version);

        $evaluacion = EvaluacionRiesgo::create([
            'diligenciamiento_id' => $diligenciamiento->id,
            'version_modelo_id' => $version->id,
            'categoria' => $prediccion['categoria'],
            'prob_0' => $prediccion['probabilidades']['0'],
            'prob_1' => $prediccion['probabilidades']['1'],
            'prob_2' => $prediccion['probabilidades']['2'],
            'contribuciones' => $contribuciones,
            'evaluado_at' => now(),
        ]);

        $this->alertas->generarSiCambioDeRiesgo($evaluacion);

        return $evaluacion;
    }
}
