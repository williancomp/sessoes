<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pautas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sessao_id')->constrained('sessoes');
            $table->foreignId('tipo_pauta_id')->constrained('tipo_pautas');
            $table->string('numero'); // Ex: "PLC 409/2025"
            $table->string('autor');
            $table->text('descricao');
            $table->integer('ordem');
            $table->enum('status', ['aguardando', 'em_discussao', 'em_votacao', 'votada'])
                  ->default('aguardando');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pautas');
    }
};