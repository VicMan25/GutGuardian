<?php

namespace App\Modules\Encuestas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opcion extends Model
{
    protected $table = 'opciones';

    protected $fillable = [
        'pregunta_id',
        'etiqueta',
        'valor_numerico',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'valor_numerico' => 'integer',
            'orden'          => 'integer',
        ];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }
}
