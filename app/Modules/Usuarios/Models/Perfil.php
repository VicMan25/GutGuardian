<?php

namespace App\Modules\Usuarios\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Perfil extends Model
{
    use SoftDeletes;

    protected $table = 'perfiles';

    protected $fillable = [
        'user_id',
        'genero',
        'edad',
        'programa_id',
        'semestre',
    ];

    protected function casts(): array
    {
        return [
            'edad' => 'integer',
            'semestre' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }
}
