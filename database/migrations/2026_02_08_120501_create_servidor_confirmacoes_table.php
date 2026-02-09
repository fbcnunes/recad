<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('servidor_confirmacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servidor_id')->constrained('servidores')->cascadeOnDelete();
            $table->string('aba', 32);
            $table->string('hash_snapshot', 64);
            $table->timestamp('confirmado_em');
            $table->foreignId('confirmado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['servidor_id', 'aba']);
            $table->index(['aba', 'confirmado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servidor_confirmacoes');
    }
};

