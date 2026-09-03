<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Panel\Models\Alerta;

class AlertaPolicy
{
    /**
     * Solo el estudiante propietario puede ver o marcar como leída su alerta.
     */
    public function view(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }

    public function update(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->user_id;
    }
}
