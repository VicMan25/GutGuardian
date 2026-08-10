<?php

namespace App\Modules\Panel\Models;

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $table = 'alertas';

    protected $fillable = [
        'user_id',
        'evaluacion_id',
        'tipo',
        'mensaje',
        'leida_at',
    ];

    protected function casts(): array
    {
        return [
            'leida_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionRiesgo::class, 'evaluacion_id');
    }
}
