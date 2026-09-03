<?php

namespace App\Http\Controllers;

use App\Modules\Reportes\Services\SeguimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EstudianteInicioController extends Controller
{
    public function __construct(private readonly SeguimientoService $seguimiento) {}

    /**
     * HU-007: resumen de las respuestas más recientes del estudiante
     * (resultado de riesgo más reciente y acceso directo a historial/seguimiento).
     */
    public function show(): View
    {
        $diligenciamientos = $this->seguimiento->diligenciamientosCompletados(Auth::user());

        return view('estudiante.inicio', [
            'ultimoDiligenciamiento' => $diligenciamientos->last(),
            'totalCompletados' => $diligenciamientos->count(),
        ]);
    }
}
