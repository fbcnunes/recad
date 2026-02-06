<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contatos_emergencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete();

            $table->string('nome');
            $table->string('celular')->nullable();
            $table->string('parentesco')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos_emergencia');
    }
};
