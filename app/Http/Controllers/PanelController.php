<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Reportes\Services\ReporteInstitucionalService;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Portada del panel institucional: indicadores de la población monitoreada
     * y distribución de niveles de riesgo. Reutiliza
     * ReporteInstitucionalService (misma fuente de verdad que HU-022/023).
     */
    public function inicio(ReporteInstitucionalService $reportes): View
    {
        $distribucion = $reportes->generar([]);

        return view('panel.inicio', [
            'totalEstudiantes' => User::role('estudiante')->count(),
            'estudiantesActivos' => User::role('estudiante')->where('activo', true)->count(),
            'encuestasCompletadas' => Diligenciamiento::where('estado', 'completado')->count(),
            'totalEvaluaciones' => $distribucion['total'],
            'resumenRiesgo' => $distribucion['resumen'],
        ]);
    }
}
