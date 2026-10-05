<?php

namespace App\Modules\Analitica\Services;

use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Panel\Services\AlertaService;
use Carbon\CarbonInterface;

/**
 * HU-009: orquesta predicción + explicabilidad, persiste la evaluación y
 * genera la alerta de cambio de riesgo (HU-012). version_modelo_id queda
 * siempre registrado (CLAUDE.md §5, trazabilidad TRIPOD+AI).
 *
 * Lo usan ResultadoController (flujo real) y el comando de simulación de
 * desarrollo, de modo que ambos evalúan exactamente igual.
 */
class EvaluacionService
{
    public function __construct(
        private readonly PredictorService $predictor,
        private readonly ExplicabilidadService $explicabilidad,
        private readonly AlertaService $alertas,
    ) {}

    public function evaluar(Diligenciamiento $diligenciamiento, ?CarbonInterface $momento = null): EvaluacionRiesgo
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
            'evaluado_at' => $momento ?? now(),
        ]);

        $this->alertas->generarSiCambioDeRiesgo($evaluacion);

        return $evaluacion;
    }
}
