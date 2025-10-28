<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vereadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome_parlamentar');
            $table->string('foto')->nullable();
            $table->foreignId('partido_id')->constrained('partidos');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users');
            $table->string('identificador_microfone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vereadores');
    }
};