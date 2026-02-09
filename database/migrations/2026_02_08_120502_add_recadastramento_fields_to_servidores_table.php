<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('servidores', function (Blueprint $table) {
            $table->timestamp('recadastramento_concluido_em')->nullable()->after('updated_at');
            $table->foreignId('recadastramento_concluido_por_user_id')
                ->nullable()
                ->after('recadastramento_concluido_em')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('servidores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recadastramento_concluido_por_user_id');
            $table->dropColumn('recadastramento_concluido_em');
        });
    }
};

