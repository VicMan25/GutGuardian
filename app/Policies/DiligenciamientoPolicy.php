<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;

class DiligenciamientoPolicy
{
    /**
     * El admin y profesional_salud pueden ver cualquier diligenciamiento.
     * El estudiante solo puede ver los suyos.
     */
    public function view(User $user, Diligenciamiento $diligenciamiento): bool
    {
        if ($user->hasAnyRole(['admin', 'profesional_salud'])) {
            return true;
        }

        return $user->id === $diligenciamiento->user_id;
    }

    /**
     * Solo el propietario puede crear respuestas sobre su propio diligenciamiento.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['estudiante', 'admin']);
    }

    /**
     * Solo el propietario puede actualizar (continuar) su diligenciamiento, y
     * solo mientras no esté completado: un diligenciamiento cerrado es un
     * registro clínico inmutable (HU-006, CLAUDE.md §5 — nunca editar tras cerrar).
     */
    public function update(User $user, Diligenciamiento $diligenciamiento): bool
    {
        return $user->id === $diligenciamiento->user_id
            && $diligenciamiento->estado !== 'completado';
    }
}
