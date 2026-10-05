<?php

namespace App\Modules\Encuestas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluacionUsabilidad extends Model
{
    protected $table = 'evaluaciones_usabilidad';

    protected $fillable = [
        'user_id',
        'respuestas',
        'puntaje',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'respuestas' => 'array',
            'puntaje' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
