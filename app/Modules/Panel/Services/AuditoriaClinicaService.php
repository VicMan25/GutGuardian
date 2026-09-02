<?php

namespace App\Modules\Panel\Services;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Registro de auditoría de todo acceso a datos clínicos por parte de un
 * profesional de salud o administrador (Ley 1581 de 2012, habeas data —
 * CLAUDE.md §9: "log de auditoría de todo acceso a datos clínicos").
 *
 * Sprint 5 es el sprint que habilita el acceso de terceros a la información
 * clínica del estudiante (ficha individual, resultado de riesgo, reportes
 * institucionales); este servicio deja rastro de quién consultó qué y cuándo.
 *
 * Alcance registrado:
 *  - consulta de la ficha individual de un estudiante (EstudianteController::show)
 *  - consulta de un resultado de riesgo de otra persona (ResultadoController::show)
 *  - consulta y exportación de reportes institucionales (ReporteController)
 *
 * No se registra el listado general (`/panel/estudiantes`): es un directorio
 * agregado, no la apertura de un registro clínico puntual. La granularidad
 * elegida es "apertura de un registro individual", documentada en
 * docs/AVANCE_PROYECTO.md §11.
 */
class AuditoriaClinicaService
{
    /** Nombre del log de spatie/laravel-activitylog reservado para estos eventos. */
    public const LOG = 'acceso_clinico';

    /**
     * Consulta de la ficha individual de un estudiante.
     */
    public function registrarConsultaFicha(User $actor, User $estudiante): void
    {
        activity(self::LOG)
            ->causedBy($actor)
            ->performedOn($estudiante)
            ->event('consulta_ficha')
            ->withProperties([
                'codigo_participante' => $estudiante->codigo_participante,
            ])
            ->log('Consultó la ficha clínica del estudiante');
    }

    /**
     * Consulta de un resultado de riesgo perteneciente a otra persona.
     * El acceso del propio estudiante a su resultado no se audita aquí.
     */
    public function registrarConsultaResultado(User $actor, Diligenciamiento $diligenciamiento): void
    {
        if ($actor->id === $diligenciamiento->user_id) {
            return;
        }

        activity(self::LOG)
            ->causedBy($actor)
            ->performedOn($diligenciamiento)
            ->event('consulta_resultado')
            ->withProperties([
                'estudiante_id' => $diligenciamiento->user_id,
                'diligenciamiento_id' => $diligenciamiento->id,
            ])
            ->log('Consultó el resultado de riesgo de un estudiante');
    }

    /**
     * Consulta o exportación de un reporte institucional.
     *
     * @param  'consulta'|'exportacion_pdf'|'exportacion_excel'  $accion
     * @param  array<string, mixed>  $filtros
     */
    public function registrarReporte(User $actor, string $accion, array $filtros = []): void
    {
        $descripciones = [
            'consulta' => 'Consultó el reporte institucional de niveles de riesgo',
            'exportacion_pdf' => 'Exportó el reporte institucional de niveles de riesgo en PDF',
            'exportacion_excel' => 'Exportó el reporte institucional de niveles de riesgo en Excel',
        ];

        activity(self::LOG)
            ->causedBy($actor)
            ->event($accion === 'consulta' ? 'consulta_reporte' : 'exportacion_reporte')
            ->withProperties([
                'accion' => $accion,
                'filtros' => array_filter($filtros, fn ($v) => $v !== null && $v !== ''),
            ])
            ->log($descripciones[$accion] ?? 'Accedió al reporte institucional');
    }

    /**
     * Consultas de auditoría registradas, más recientes primero.
     *
     * @return Collection<int, Activity>
     */
    public function registros()
    {
        return Activity::inLog(self::LOG)->latest()->get();
    }
}
