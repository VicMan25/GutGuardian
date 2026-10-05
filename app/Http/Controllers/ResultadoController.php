<?php

namespace App\Http\Controllers;

use App\Modules\Analitica\Services\EvaluacionService;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Panel\Services\AuditoriaClinicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function __construct(
        private readonly EvaluacionService $evaluaciones,
        private readonly AuditoriaClinicaService $auditoria,
    ) {}

    public function show(Request $request, Diligenciamiento $diligenciamiento): View|RedirectResponse
    {
        $this->authorize('view', $diligenciamiento);

        if ($diligenciamiento->estado !== 'completado') {
            return back()->with('error', 'Debes completar la encuesta antes de ver tu resultado.');
        }

        // Ley 1581 de 2012 — auditar solo el acceso de terceros (profesional/admin),
        // no el del propio estudiante a su resultado.
        $this->auditoria->registrarConsultaResultado($request->user(), $diligenciamiento);

        $evaluacion = $diligenciamiento->evaluaciones()->latest('evaluado_at')->first()
            ?? $this->evaluaciones->evaluar($diligenciamiento);

        return view('estudiante.resultado', [
            'evaluacion' => $evaluacion,
            'diligenciamiento' => $diligenciamiento,
        ]);
    }
}
