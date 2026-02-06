<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certidoes_servidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete()->unique();

            $table->string('tipo')->nullable();
            $table->string('registro_num')->nullable();
            $table->string('livro')->nullable();
            $table->string('folha')->nullable();
            $table->string('matricula')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certidoes_servidor');
    }
};
