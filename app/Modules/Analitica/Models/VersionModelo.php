<?php

namespace App\Modules\Analitica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VersionModelo extends Model
{
    protected $table = 'versiones_modelo';

    protected $fillable = [
        'nombre',
        'version',
        'entrenado_at',
        'activo',
        'coeficientes',
        'metricas',
        'mapa_variables',
    ];

    protected function casts(): array
    {
        return [
            'entrenado_at' => 'datetime',
            'activo' => 'boolean',
            'coeficientes' => 'array',
            'metricas' => 'array',
            'mapa_variables' => 'array',
        ];
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionRiesgo::class);
    }
}
