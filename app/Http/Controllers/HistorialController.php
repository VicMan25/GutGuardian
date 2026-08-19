<?php

namespace App\Http\Controllers;

use App\Modules\Reportes\Services\SeguimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function __construct(private readonly SeguimientoService $seguimiento) {}

    /**
     * HU-007: historial cronológico de diligenciamientos completados del
     * estudiante autenticado, con su categoría de riesgo.
     */
    public function show(): View
    {
        $diligenciamientos = $this->seguimiento->diligenciamientosCompletados(Auth::user())->reverse();

        return view('estudiante.historial', [
            'diligenciamientos' => $diligenciamientos,
        ]);
    }
}
