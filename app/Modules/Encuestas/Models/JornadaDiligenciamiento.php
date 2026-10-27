<?php

namespace App\Modules\Encuestas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JornadaDiligenciamiento extends Model
{
    protected $table = 'jornadas_diligenciamiento';

    protected $fillable = [
        'diligenciamiento_id',
        'jornada',
        'iniciada_at',
        'completada_at',
        'recordatorio_enviado_at',
    ];

    protected function casts(): array
    {
        return [
            'jornada' => 'integer',
            'iniciada_at' => 'datetime',
            'completada_at' => 'datetime',
            'recordatorio_enviado_at' => 'datetime',
        ];
    }

    public function diligenciamiento(): BelongsTo
    {
        return $this->belongsTo(Diligenciamiento::class);
    }
}
