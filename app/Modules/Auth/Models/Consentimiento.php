<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consentimiento extends Model
{
    protected $table = 'consentimientos';

    protected $fillable = [
        'user_id',
        'version_politica',
        'aceptado_at',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'aceptado_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
