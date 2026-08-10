<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;

class EvaluacionRiesgoPolicy
{
    /**
     * El admin y profesional_salud pueden ver cualquier evaluación.
     * El estudiante solo puede ver evaluaciones de sus propios diligenciamientos.
     */
    public function view(User $user, EvaluacionRiesgo $evaluacion): bool
    {
        if ($user->hasAnyRole(['admin', 'profesional_salud'])) {
            return true;
        }

        return $user->id === $evaluacion->diligenciamiento->user_id;
    }
}
