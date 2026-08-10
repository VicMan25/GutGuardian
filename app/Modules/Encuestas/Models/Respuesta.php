<?php

namespace App\Modules\Encuestas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Respuesta extends Model
{
    use SoftDeletes;

    protected $table = 'respuestas';

    protected $fillable = [
        'diligenciamiento_id',
        'pregunta_id',
        'item_pregunta_id',
        'opcion_id',
        'valor_numerico',
        'valor_texto',
    ];

    protected function casts(): array
    {
        return [
            'valor_numerico' => 'integer',
        ];
    }

    public function diligenciamiento(): BelongsTo
    {
        return $this->belongsTo(Diligenciamiento::class);
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemPregunta::class, 'item_pregunta_id');
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class);
    }
}
