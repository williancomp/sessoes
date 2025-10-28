<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presencas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_id')->constrained('sessoes')->cascadeOnDelete(); // Liga com a Sessao
            $table->foreignId('vereador_id')->constrained('vereadores')->cascadeOnDelete(); // Liga com o Vereador
            $table->boolean('presente')->default(false);
            $table->timestamp('horario_login')->nullable(); // Guarda a hora exata do login
            $table->timestamps(); // created_at e updated_at

            // Garante que só haja um registro de presença por vereador por sessão
            $table->unique(['sessao_id', 'vereador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presencas');
    }
};
