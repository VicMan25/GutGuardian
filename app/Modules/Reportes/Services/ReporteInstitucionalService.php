<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Analitica\Models\EvaluacionRiesgo;
use Illuminate\Support\Collection;

/**
 * HU-022/HU-023: distribución de niveles de riesgo de todas las evaluaciones
 * realizadas, dentro del rango de fechas y/o categoría filtrados. Única
 * fuente de verdad reutilizada por la vista, el PDF (HU-024) y el Excel
 * (HU-024) — evita triplicar la consulta.
 *
 * Cada diligenciamiento se evalúa una sola vez (ResultadoController reutiliza
 * la evaluación existente en vez de recalcular, ver HU-009), así que no hace
 * falta deduplicar por estudiante: una fila por evaluación ya es una fila por
 * diligenciamiento completado.
 */
class ReporteInstitucionalService
{
    private const ETIQUETAS = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];

    /**
     * @param  array{desde?: ?string, hasta?: ?string, categoria?: ?int}  $filtros
     * @return array{resumen: array<int, array{categoria: int, etiqueta: string, total: int, porcentaje: float}>, detalle: Collection<int, array<string, mixed>>, total: int}
     */
    public function generar(array $filtros): array
    {
        $query = EvaluacionRiesgo::query()->with(['diligenciamiento.usuario.perfil.programa']);

        if (! empty($filtros['desde'])) {
            $query->whereDate('evaluado_at', '>=', $filtros['desde']);
        }

        if (! empty($filtros['hasta'])) {
            $query->whereDate('evaluado_at', '<=', $filtros['hasta']);
        }

        if (isset($filtros['categoria']) && $filtros['categoria'] !== null && $filtros['categoria'] !== '') {
            $query->where('categoria', (int) $filtros['categoria']);
        }

        $evaluaciones = $query->get();
        $total = $evaluaciones->count();

        $resumen = [];
        foreach ([0, 1, 2] as $categoria) {
            $enCategoria = $evaluaciones->where('categoria', $categoria)->count();
            $resumen[] = [
                'categoria' => $categoria,
                'etiqueta' => self::ETIQUETAS[$categoria],
                'total' => $enCategoria,
                'porcentaje' => $total > 0 ? round($enCategoria / $total * 100, 1) : 0.0,
            ];
        }

        $detalle = $evaluaciones
            ->sortByDesc('evaluado_at')
            ->map(fn (EvaluacionRiesgo $e) => [
                'estudiante' => $e->diligenciamiento->usuario->name,
                'codigo_participante' => $e->diligenciamiento->usuario->codigo_participante,
                'programa' => $e->diligenciamiento->usuario->perfil?->programa?->nombre ?? '—',
                'categoria' => self::ETIQUETAS[$e->categoria],
                'fecha' => $e->evaluado_at->format('d/m/Y'),
            ])
            ->values();

        return compact('resumen', 'detalle', 'total');
    }
}
