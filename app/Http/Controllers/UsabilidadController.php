<?php

namespace App\Http\Controllers;

use App\Modules\Reportes\Services\UsabilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cuestionario SUS de la prueba piloto (Sprint 6, objetivo 1.3.2.4).
 * Voluntario, una sola vez por estudiante y solo después de haber completado
 * al menos una encuesta: el SUS evalúa la experiencia de uso, que sin haber
 * usado el aplicativo no existe.
 */
class UsabilidadController extends Controller
{
    public function __construct(private readonly UsabilidadService $usabilidad) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $this->puedeResponder($request)) {
            return redirect()->route('estudiante.inicio');
        }

        return view('estudiante.usabilidad', [
            'evaluacion' => $user->evaluacionUsabilidad,
            'items' => UsabilidadService::ITEMS,
            'escala' => UsabilidadService::ESCALA,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->puedeResponder($request) || $user->evaluacionUsabilidad()->exists()) {
            return redirect()->route('usabilidad.show');
        }

        $reglas = ['comentario' => ['nullable', 'string', 'max:1000']];
        $mensajes = [];
        foreach (UsabilidadService::ITEMS as $item => $_) {
            $reglas["respuestas.$item"] = ['required', 'integer', 'between:1,5'];
            $mensajes["respuestas.$item.required"] = "Responde la afirmación {$item}.";
        }

        $datos = $request->validate($reglas, $mensajes);

        $respuestas = array_map('intval', $datos['respuestas']);
        ksort($respuestas);

        $this->usabilidad->registrar($user, $respuestas, $datos['comentario'] ?? null);

        return redirect()->route('usabilidad.show')->with('status', 'usabilidad-registrada');
    }

    private function puedeResponder(Request $request): bool
    {
        return $request->user()->diligenciamientos()->where('estado', 'completado')->exists();
    }
}
