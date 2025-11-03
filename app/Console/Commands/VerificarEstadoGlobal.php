<?php

namespace App\Console\Commands;

use App\Services\EstadoGlobalService;
use Illuminate\Console\Command;

class VerificarEstadoGlobal extends Command
{
    protected $signature = 'estado:verificar';
    protected $description = 'Verifica o estado global atual';

    public function handle()
    {
        $service = app(EstadoGlobalService::class);
        
        $this->info('=== ESTADO GLOBAL ATUAL ===');
        
        $estado = $service->getEstadoCompleto();
        
        $this->info('Layout do Telão:');
        $layout = $service->getTelaoLayout();
        $this->line('  Layout: ' . ($layout['layout'] ?? 'null'));
        $this->line('  Dados: ' . json_encode($layout['dados'] ?? null, JSON_PRETTY_PRINT));
        
        $this->info('Votação Ativa (Cache):');
        $votacao = $service->getVotacaoAtiva();
        if ($votacao) {
            $this->line('  Pauta: ' . ($votacao['numero'] ?? 'N/A') . ' - ' . strip_tags($votacao['descricao'] ?? 'N/A'));
        } else {
            $this->line('  Nenhuma votação ativa no cache');
        }
        
        $this->info('Votação Ativa (Banco):');
        $pautasVotacao = \App\Models\Pauta::where('status', 'em_votacao')->get();
        if ($pautasVotacao->count() > 0) {
            foreach ($pautasVotacao as $pauta) {
                $this->line('  Pauta: ' . $pauta->numero . ' - ' . strip_tags($pauta->descricao) . ' (Status: ' . $pauta->status . ')');
            }
        } else {
            $this->line('  Nenhuma pauta em votação no banco');
        }
        
        $this->info('Status da Pauta PLC 01/2025:');
        $pautaPLC = \App\Models\Pauta::where('numero', 'PLC 01/2025')->first();
        if ($pautaPLC) {
            $this->line('  Status: ' . $pautaPLC->status);
        } else {
            $this->line('  Pauta não encontrada');
        }
        
        $this->info('Palavra Ativa:');
        $palavra = $service->getPalavraAtiva();
        if ($palavra && isset($palavra['vereador'])) {
            $this->line('  Vereador: ' . ($palavra['vereador']['nome_parlamentar'] ?? 'N/A'));
            $this->line('  Status: ' . ($palavra['status'] ?? 'N/A'));
        } else {
            $this->line('  Nenhuma palavra ativa');
        }
        
        return 0;
    }
}