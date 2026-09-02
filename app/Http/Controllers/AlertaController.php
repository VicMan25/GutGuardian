<?php

namespace App\Http\Controllers;

use App\Modules\Panel\Models\Alerta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertaController extends Controller
{
    /**
     * HU-012: lista las alertas internas de cambio de riesgo del estudiante
     * autenticado, más recientes primero.
     */
    public function index(): View
    {
        return view('estudiante.alertas', [
            'alertas' => Auth::user()->alertas()->latest()->get(),
        ]);
    }

    public function marcarLeida(Alerta $alerta): RedirectResponse
    {
        $this->authorize('update', $alerta);

        $alerta->update(['leida_at' => now()]);

        return back();
    }
}
