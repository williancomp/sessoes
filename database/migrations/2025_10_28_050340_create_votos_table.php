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
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pauta_id')->constrained('pautas')->cascadeOnDelete(); // Liga com a Pauta
            $table->foreignId('vereador_id')->constrained('vereadores')->cascadeOnDelete(); // Liga com o Vereador
            $table->enum('voto', ['sim', 'nao', 'abst']); // Opções de voto
            $table->timestamps(); // created_at será o momento do voto

            // Garante que um vereador só vote uma vez por pauta
            $table->unique(['pauta_id', 'vereador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
