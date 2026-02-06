<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependente extends Model
{
    protected $table = 'dependentes';

    protected $guarded = [];

    protected $casts = [
        'nascimento' => 'date',
        'rg_expedicao' => 'date',
    ];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
