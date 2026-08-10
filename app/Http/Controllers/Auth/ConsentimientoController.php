<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\Consentimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsentimientoController extends Controller
{
    public function show(): View
    {
        return view('auth.consentimiento');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'acepto' => ['accepted'],
        ], [
            'acepto.accepted' => 'Debes aceptar el consentimiento informado para continuar.',
        ]);

        Consentimiento::create([
            'user_id' => $request->user()->id,
            'version_politica' => '1.0',
            'aceptado_at' => now(),
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('estudiante.inicio'));
    }
}
