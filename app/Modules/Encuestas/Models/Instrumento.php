<?php

namespace App\Modules\Encuestas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instrumento extends Model
{
    protected $table = 'instrumentos';

    protected $fillable = [
        'nombre',
        'version',
        'tipo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class)->orderBy('orden');
    }

    public function diligenciamientos(): HasMany
    {
        return $this->hasMany(Diligenciamiento::class);
    }
}
