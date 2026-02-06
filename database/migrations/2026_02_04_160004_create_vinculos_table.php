<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete()->unique();

            $table->string('forma_ingresso')->nullable();
            $table->date('data_ingresso')->nullable();
            $table->date('nomeacao_cessao_data')->nullable();
            $table->string('portaria_num')->nullable();
            $table->string('doe_num')->nullable();
            $table->date('doe_publicacao_data')->nullable();
            $table->string('cargo_funcao')->nullable();
            $table->string('orgao_origem')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculos');
    }
};
