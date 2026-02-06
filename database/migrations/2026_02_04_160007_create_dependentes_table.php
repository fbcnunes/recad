<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dependentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete();

            $table->string('nome');
            $table->string('parentesco')->nullable();
            $table->date('nascimento')->nullable();
            $table->string('rg_num')->nullable();
            $table->date('rg_expedicao')->nullable();
            $table->string('cpf')->nullable();

            $table->string('certidao_tipo')->nullable();
            $table->string('sexo')->nullable();
            $table->string('tipo_dependente')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependentes');
    }
};
