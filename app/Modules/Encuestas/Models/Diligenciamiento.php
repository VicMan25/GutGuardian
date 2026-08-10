<?php

namespace App\Modules\Encuestas\Models;

use App\Models\User;
use App\Modules\Analitica\Models\EvaluacionRiesgo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diligenciamiento extends Model
{
    use SoftDeletes;

    protected $table = 'diligenciamientos';

    protected $fillable = [
        'user_id',
        'instrumento_id',
        'estado',
        'completado_at',
    ];

    protected function casts(): array
    {
        return [
            'completado_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionRiesgo::class);
    }
}
