<?php

namespace App\Http\Controllers;

use App\Modules\Reportes\Services\SeguimientoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EstudianteInicioController extends Controller
{
    public function __construct(private readonly SeguimientoService $seguimiento) {}

    public function show(): View
    {
        $diligenciamientos = $this->seguimiento->diligenciamientosCompletados(Auth::user());

        return view('estudiante.inicio', [
            'ultimoDiligenciamiento' => $diligenciamientos->last(),
            'totalCompletados' => $diligenciamientos->count(),
        ]);
    }
}
