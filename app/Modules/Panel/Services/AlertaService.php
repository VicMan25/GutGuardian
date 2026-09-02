<?php

namespace App\Modules\Panel\Services;

use App\Modules\Analitica\Models\EvaluacionRiesgo;
use App\Modules\Panel\Models\Alerta;

/**
 * HU-012: genera una alerta interna cuando el nivel de riesgo de un
 * estudiante cambia respecto a su evaluación anterior.
 */
class AlertaService
{
    private const ETIQUETAS_CATEGORIA = ['riesgo bajo', 'riesgo medio', 'riesgo alto'];

    /**
     * Si existe una evaluación previa del mismo estudiante con una categoría
     * distinta a la de $evaluacion, crea la alerta correspondiente. Si es la
     * primera evaluación del estudiante o la categoría no cambió, no genera
     * nada (criterios 2 y 7 de la HU-012).
     */
    public function generarSiCambioDeRiesgo(EvaluacionRiesgo $evaluacion): ?Alerta
    {
        $userId = $evaluacion->diligenciamiento->user_id;

        $anterior = EvaluacionRiesgo::whereHas(
            'diligenciamiento',
            fn ($q) => $q->where('user_id', $userId)
        )
            ->where('id', '!=', $evaluacion->id)
            ->where('evaluado_at', '<', $evaluacion->evaluado_at)
            ->latest('evaluado_at')
            ->first();

        if (! $anterior || $anterior->categoria === $evaluacion->categoria) {
            return null;
        }

        return Alerta::create([
            'user_id' => $userId,
            'evaluacion_id' => $evaluacion->id,
            'tipo' => 'cambio_riesgo',
            'mensaje' => sprintf(
                'Tu nivel de riesgo cambió de %s a %s.',
                self::ETIQUETAS_CATEGORIA[$anterior->categoria],
                self::ETIQUETAS_CATEGORIA[$evaluacion->categoria]
            ),
        ]);
    }
}
