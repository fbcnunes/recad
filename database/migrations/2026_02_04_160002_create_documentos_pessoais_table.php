<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos_pessoais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete()->unique();

            $table->string('rg_num')->nullable();
            $table->string('rg_uf', 2)->nullable();
            $table->date('rg_expedicao')->nullable();

            $table->string('cpf')->nullable()->unique();

            $table->string('id_prof_num')->nullable();
            $table->string('id_prof_tipo')->nullable();
            $table->string('id_prof_uf', 2)->nullable();

            $table->string('cnh_num')->nullable();
            $table->string('cnh_categoria')->nullable();
            $table->date('cnh_validade')->nullable();
            $table->string('cnh_uf', 2)->nullable();

            $table->string('ctps_num')->nullable();
            $table->string('ctps_serie')->nullable();
            $table->date('ctps_expedicao')->nullable();

            $table->string('titulo_eleitor_num')->nullable();
            $table->string('titulo_zona')->nullable();
            $table->string('titulo_secao')->nullable();

            $table->string('reservista_num')->nullable();
            $table->string('reservista_categoria')->nullable();
            $table->string('reservista_uf', 2)->nullable();

            $table->string('pis_pasep')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_pessoais');
    }
};
