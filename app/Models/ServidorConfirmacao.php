<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServidorConfirmacao extends Model
{
    protected $table = 'servidor_confirmacoes';

    protected $guarded = [];

    protected $casts = [
        'confirmado_em' => 'datetime',
    ];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por_user_id');
    }
}

