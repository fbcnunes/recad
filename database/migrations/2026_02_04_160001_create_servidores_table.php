<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servidores', function (Blueprint $table) {
            $table->id();
            $table->string('matricula')->unique();
            $table->string('nome');
            $table->string('pai')->nullable();
            $table->string('mae')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('conjuge_nome')->nullable();
            $table->string('naturalidade')->nullable();
            $table->string('naturalidade_uf', 2)->nullable();
            $table->string('nacionalidade')->nullable();

            $table->string('escolaridade')->nullable();
            $table->string('curso')->nullable();
            $table->string('pos_graduacao')->nullable();
            $table->string('pos_curso')->nullable();
            $table->date('pos_inicio')->nullable();
            $table->date('pos_fim')->nullable();
            $table->unsignedInteger('pos_carga_horaria')->nullable();

            $table->string('sexo')->nullable();
            $table->string('tipo_sanguineo', 3)->nullable();
            $table->string('fator_rh', 3)->nullable();
            $table->string('raca_cor')->nullable();

            $table->string('endereco')->nullable();
            $table->string('numero')->nullable();
            $table->string('bairro')->nullable();
            $table->string('complemento')->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('cidade')->nullable();
            $table->string('cidade_uf', 2)->nullable();

            $table->string('fone_fixo')->nullable();
            $table->string('celular')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('plano_saude')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servidores');
    }
};
