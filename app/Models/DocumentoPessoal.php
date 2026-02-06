<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoPessoal extends Model
{
    protected $table = 'documentos_pessoais';

    protected $guarded = [];

    protected $casts = [
        'rg_expedicao' => 'date',
        'cnh_validade' => 'date',
        'ctps_expedicao' => 'date',
    ];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
