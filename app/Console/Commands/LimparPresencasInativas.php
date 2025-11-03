<?php

namespace App\Console\Commands;

use App\Events\PresencaAtualizada;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\Vereador;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LimparPresencasInativas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'presenca:limpar-inativas {--timeout=300}';

    /**
     * The console command description.
     */
    protected $description = 'Remove presenças de vereadores inativos há mais de X segundos (padrão: 300s = 5min)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeoutSegundos = (int) $this->option('timeout');
        $limiteInatividade = Carbon::now()->subSeconds($timeoutSegundos);
        
        Log::info('LimparPresencasInativas: Iniciando limpeza', [
            'timeout_segundos' => $timeoutSegundos,
            'limite_inatividade' => $limiteInatividade->toDateTimeString()
        ]);

        // Busca sessões ativas
        $sessoesAtivas = Sessao::whereIn('status', ['agendada', 'em_andamento'])->get();
        
        if ($sessoesAtivas->isEmpty()) {
            $this->info('Nenhuma sessão ativa encontrada.');
            return 0;
        }

        $totalLimpas = 0;

        foreach ($sessoesAtivas as $sessao) {
            // Busca presenças que estão marcadas como presentes mas não têm atividade recente
            $presencasInativas = Presenca::where('sessao_id', $sessao->id)
                ->where('presente', true)
                ->where('horario_login', '<', $limiteInatividade)
                ->get();

            if ($presencasInativas->isEmpty()) {
                continue;
            }

            $this->info("Sessão {$sessao->id}: Encontradas {$presencasInativas->count()} presenças inativas");

            foreach ($presencasInativas as $presenca) {
                // Marca como ausente
                $presenca->update([
                    'presente' => false,
                    'horario_login' => null,
                ]);

                $vereador = Vereador::find($presenca->vereador_id);
                $nomeVereador = $vereador ? $vereador->nome_parlamentar : $presenca->vereador_id;
                $this->info("  - Vereador {$nomeVereador} marcado como ausente por inatividade");
                
                $totalLimpas++;
            }

            // Recalcula e dispara evento de presença atualizada
            $totalVereadores = Vereador::count();
            $presentes = Presenca::where('sessao_id', $sessao->id)->where('presente', true)->count();
            $ausentes = $totalVereadores - $presentes;

            broadcast(new PresencaAtualizada($sessao->id, $presentes, $ausentes));

            Log::info('LimparPresencasInativas: Evento PresencaAtualizada disparado', [
                'sessao_id' => $sessao->id,
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'limpas_nesta_sessao' => $presencasInativas->count()
            ]);
        }

        $this->info("Limpeza concluída. Total de presenças inativas removidas: {$totalLimpas}");
        
        Log::info('LimparPresencasInativas: Limpeza concluída', [
            'total_limpas' => $totalLimpas
        ]);

        return 0;
    }
}