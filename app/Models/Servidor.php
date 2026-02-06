<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Servidor extends Model
{
    protected $table = 'servidores';

    protected $guarded = [];

    protected $casts = [
        'data_nascimento' => 'date',
        'pos_inicio' => 'date',
        'pos_fim' => 'date',
    ];

    public function documentoPessoal(): HasOne
    {
        return $this->hasOne(DocumentoPessoal::class);
    }

    public function certidaoServidor(): HasOne
    {
        return $this->hasOne(CertidaoServidor::class);
    }

    public function vinculo(): HasOne
    {
        return $this->hasOne(Vinculo::class);
    }

    public function contaBancaria(): HasOne
    {
        return $this->hasOne(ContaBancaria::class);
    }

    public function contatosEmergencia(): HasMany
    {
        return $this->hasMany(ContatoEmergencia::class);
    }

    public function dependentes(): HasMany
    {
        return $this->hasMany(Dependente::class);
    }
}
