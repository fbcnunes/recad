<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vinculo extends Model
{
    protected $table = 'vinculos';

    protected $guarded = [];

    protected $casts = [
        'data_ingresso' => 'date',
        'nomeacao_cessao_data' => 'date',
        'doe_publicacao_data' => 'date',
    ];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
