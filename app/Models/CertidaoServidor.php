<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertidaoServidor extends Model
{
    protected $table = 'certidoes_servidor';

    protected $guarded = [];

    public function servidor(): BelongsTo
    {
        return $this->belongsTo(Servidor::class);
    }
}
