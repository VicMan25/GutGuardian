<?php

namespace App\Modules\Analitica\Models;

use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Panel\Models\Alerta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionRiesgo extends Model
{
    protected $table = 'evaluaciones_riesgo';

    protected $fillable = [
        'diligenciamiento_id',
        'version_modelo_id',
        'categoria',
        'prob_0',
        'prob_1',
        'prob_2',
        'contribuciones',
        'evaluado_at',
    ];

    protected function casts(): array
    {
        return [
            'categoria'    => 'integer',
            'prob_0'       => 'float',
            'prob_1'       => 'float',
            'prob_2'       => 'float',
            'contribuciones' => 'array',
            'evaluado_at'  => 'datetime',
        ];
    }

    public function diligenciamiento(): BelongsTo
    {
        return $this->belongsTo(Diligenciamiento::class);
    }

    public function versionModelo(): BelongsTo
    {
        return $this->belongsTo(VersionModelo::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'evaluacion_id');
    }
}
