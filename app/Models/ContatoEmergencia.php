<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContatoEmergencia extends Model
{
    protected $table = 'contatos_emergencia';

    protected $guarded = [];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
