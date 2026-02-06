<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaBancaria extends Model
{
    protected $table = 'contas_bancarias';

    protected $guarded = [];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
