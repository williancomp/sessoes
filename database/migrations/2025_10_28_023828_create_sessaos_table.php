<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sessoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislatura_id')->constrained('legislaturas');
            $table->date('data');
            $table->enum('tipo', ['ordinaria', 'extraordinaria', 'solene']);
            $table->enum('status', ['agendada', 'em_andamento', 'concluida'])
                  ->default('agendada');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sessoes');
    }
};