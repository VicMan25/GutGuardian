<?php

namespace App\Modules\Usuarios\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    protected $table = 'programas';

    protected $fillable = ['nombre'];

    public function perfiles(): HasMany
    {
        return $this->hasMany(Perfil::class);
    }
}
