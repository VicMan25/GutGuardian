<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Panel\Services\AuditoriaClinicaService;
use App\Modules\Reportes\Services\SeguimientoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstudianteController extends Controller
{
    public function __construct(
        private readonly SeguimientoService $seguimiento,
        private readonly AuditoriaClinicaService $auditoria,
    ) {}

    /**
     * HU-015/HU-016: listado general de estudiantes con su nivel de riesgo
     * más reciente, con búsqueda por nombre, código o programa.
     */
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));

        $estudiantes = User::role('estudiante')
            ->with(['perfil.programa', 'diligenciamientos' => fn ($q) => $q
                ->where('estado', 'completado')
                ->with(['evaluaciones' => fn ($q) => $q->latest('evaluado_at')])
                ->latest('completado_at'),
            ])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('codigo_participante', 'like', "%{$busqueda}%")
                        ->orWhereHas('perfil.programa', fn ($p) => $p->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('panel.estudiantes.index', [
            'estudiantes' => $estudiantes,
            'busqueda' => $busqueda,
        ]);
    }

    /**
     * HU-014/HU-016: consulta individual — historial y evolución del riesgo
     * de un estudiante puntual.
     */
    public function show(Request $request, User $estudiante): View
    {
        abort_unless($estudiante->hasRole('estudiante'), 404);

        // Ley 1581 de 2012 — deja rastro de quién consultó este registro clínico.
        $this->auditoria->registrarConsultaFicha($request->user(), $estudiante);

        $diligenciamientos = $this->seguimiento->diligenciamientosCompletados($estudiante);

        return view('panel.estudiantes.show', [
            'estudiante' => $estudiante,
            'diligenciamientos' => $diligenciamientos->reverse(),
            'riesgo' => $diligenciamientos->count() >= 2 ? $this->seguimiento->evolucionRiesgo($diligenciamientos) : null,
        ]);
    }
}
