<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Panel\Services\AuditoriaClinicaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derechos del titular de los datos (Ley 1581 de 2012, art. 8): conocer la
 * información que el sistema guarda de él (acceso), saber qué uso se le ha
 * dado —quién consultó su información clínica y cuándo— y obtener una copia.
 * La rectificación se hace desde /perfil (HU-013).
 */
class MisDatosController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('estudiante.mis-datos', [
            'user' => $user->load(['perfil.programa', 'consentimientos']),
            'totalEncuestas' => $user->diligenciamientos()->where('estado', 'completado')->count(),
            'accesos' => $this->accesosDeTerceros($user),
        ]);
    }

    public function descargar(Request $request): Response
    {
        $user = $request->user()->load([
            'perfil.programa',
            'consentimientos',
            'diligenciamientos' => fn ($q) => $q->where('estado', 'completado')->orderBy('completado_at'),
            'diligenciamientos.evaluaciones.versionModelo',
            'diligenciamientos.respuestas' => fn ($q) => $q->with(['pregunta', 'item', 'opcion'])->orderBy('pregunta_id'),
        ]);

        return Pdf::loadView('estudiante.pdf.mis-datos', [
            'user' => $user,
            'accesos' => $this->accesosDeTerceros($user),
        ])->download('mis-datos-gutguardian.pdf');
    }

    /**
     * Consultas de terceros (profesional o administrador) a la información
     * clínica de este estudiante, tomadas del log de auditoría.
     *
     * @return Collection<int, Activity>
     */
    private function accesosDeTerceros(User $user): Collection
    {
        return Activity::inLog(AuditoriaClinicaService::LOG)
            ->where(function (Builder $q) use ($user) {
                $q->where(fn ($q) => $q->where('subject_type', $user->getMorphClass())->where('subject_id', $user->id))
                    ->orWhere('properties->estudiante_id', $user->id);
            })
            ->where('causer_id', '!=', $user->id)
            ->with('causer')
            ->latest()
            ->get();
    }
}
