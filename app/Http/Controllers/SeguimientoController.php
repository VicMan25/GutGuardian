<?php

namespace App\Http\Controllers;

use App\Modules\Reportes\Services\SeguimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SeguimientoController extends Controller
{
    // Una sola encuesta no permite mostrar "evolución"; se requieren al menos 2 puntos.
    private const MINIMO_PARA_EVOLUCION = 2;

    public function __construct(private readonly SeguimientoService $seguimiento) {}

    /**
     * HU-008 (real): evolución del riesgo. El resto de las gráficas (dolor
     * abdominal, grid de síntomas) es funcionalidad complementaria sin HU
     * numerada en el documento fuente — ver docs/HISTORIAS_USUARIO.md, nota 3.
     */
    public function show(): View
    {
        $diligenciamientos = $this->seguimiento->diligenciamientosCompletados(Auth::user());

        if ($diligenciamientos->count() < self::MINIMO_PARA_EVOLUCION) {
            return view('estudiante.seguimiento', ['suficientesDatos' => false]);
        }

        $respuestas = $this->seguimiento->respuestasClinicas($diligenciamientos);

        return view('estudiante.seguimiento', [
            'suficientesDatos' => true,
            'riesgo' => $this->seguimiento->evolucionRiesgo($diligenciamientos),
            'dolor' => $this->seguimiento->evolucionDolor($diligenciamientos, $respuestas),
            'sintomas' => $this->seguimiento->seguimientoSintomas($diligenciamientos, $respuestas),
        ]);
    }
}
