<?php

namespace App\Filament\Widgets;

use App\Models\Pauta;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\Vereador;
use App\Models\Voto;
use App\Services\EstadoGlobalService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;

class StatusVereadoresWidget extends Widget
{
    protected string $view = 'filament.widgets.status-vereadores-widget';
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;
    
    // IMPORTANTE: Adicionar wire:poll para atualização periódica como fallback
    protected static ?int $pollingInterval = 2; // Atualiza a cada 2 segundos

    public ?int $sessaoAtivaId = null;
    public ?int $pautaEmVotacaoId = null;
    public Collection $vereadores;
    public array $presenca = [];
    public array $votos = [];
    
    // Adiciona contador para forçar re-render
    public int $updateCounter = 0;

    public function mount(): void
    {
        $this->vereadores = new Collection();
        $this->carregarDados();
    }

    /**
     * Método de polling para atualizar votos periodicamente
     */
    public function poll(): void
    {
        if ($this->pautaEmVotacaoId) {
            $votosAtuais = Voto::where('pauta_id', $this->pautaEmVotacaoId)
                ->pluck('voto', 'vereador_id')
                ->toArray();
            
            // Só atualiza se houver mudança
            if ($votosAtuais !== $this->votos) {
                Log::info('StatusVereadores: Detectada mudança nos votos via polling', [
                    'antes' => count($this->votos),
                    'depois' => count($votosAtuais)
                ]);
                $this->votos = $votosAtuais;
                $this->updateCounter++; // Força re-render
            }
        }
    }

    /**
     * LIVEWIRE 3: Listeners com sintaxe atualizada
     */
    protected function getListeners(): array
    {
        return [
            'echo:sessao-plenaria,PresencaAtualizada' => 'handlePresencaAtualizada',
            'echo:sessao-plenaria,VotoRegistrado' => 'handleVotoRegistrado',
            'echo:sessao-plenaria,VotacaoAberta' => 'handleVotacaoAberta',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'handleVotacaoEncerrada',
            'sessaoIniciada' => 'carregarDados',
            'sessaoEncerrada' => 'limparDados',
            'votoRegistradoLocal' => 'handleVotoRegistradoLocal', // Novo evento local
            'refresh-widget' => '$refresh', // Para refresh manual
        ];
    }

    public function carregarDados(): void
    {
        Log::info('StatusVereadores: Carregando dados');
        
        $sessao = app(EstadoGlobalService::class)->getSessaoAtiva();
        $this->sessaoAtivaId = $sessao['id'] ?? null;

        if (!$this->sessaoAtivaId) {
            $this->limparDados();
            return;
        }

        // Carrega todos os vereadores
        $this->vereadores = Vereador::with('partido')->orderBy('nome_parlamentar')->get();

        // Carrega presença inicial
        $presencas = Presenca::where('sessao_id', $this->sessaoAtivaId)
            ->get();
        
        $this->presenca = $presencas->pluck('presente', 'vereador_id')->toArray();

        // Usa EstadoGlobalService para obter votação ativa
        $votacaoAtiva = app(EstadoGlobalService::class)->getVotacaoAtiva();
        $this->pautaEmVotacaoId = $votacaoAtiva['pauta_id'] ?? null;

        if ($this->pautaEmVotacaoId) {
            $this->votos = Voto::where('pauta_id', $this->pautaEmVotacaoId)
                ->pluck('voto', 'vereador_id')
                ->toArray();
            
            Log::info('StatusVereadores: Votos carregados', [
                'pauta_id' => $this->pautaEmVotacaoId,
                'total_votos' => count($this->votos),
                'votos' => $this->votos
            ]);
        } else {
            // Se não há votação ativa, limpa os votos
            $this->votos = [];
            Log::info('StatusVereadores: Nenhuma votação ativa - votos limpos');
        }
        
        $this->updateCounter++;
    }

    public function limparDados(): void
    {
        $this->sessaoAtivaId = null;
        $this->pautaEmVotacaoId = null;
        $this->vereadores = new Collection();
        $this->presenca = [];
        $this->votos = [];
        $this->updateCounter = 0;
    }

    // --- Handlers de Eventos ---

    public function handlePresencaAtualizada($event): void
    {
        Log::info('StatusVereadores: PresencaAtualizada recebido', ['event' => $event]);
        
        if (($event['sessaoId'] ?? null) == $this->sessaoAtivaId) {
            // CORREÇÃO: Implementação mais robusta para atualização em tempo real
            
            // 1. Recarrega dados de presença diretamente do banco
            $this->recarregarPresenca();
            
            // 2. Força múltiplas estratégias de re-render
            $this->updateCounter++;
            
            // 3. Força refresh do componente Livewire
            $this->dispatch('$refresh');
            
            // 4. Força re-render do componente pai (se houver)
            $this->dispatch('refresh-widget');
            
            Log::info('StatusVereadores: Presença atualizada com sucesso', [
                'sessao_id' => $this->sessaoAtivaId,
                'update_counter' => $this->updateCounter,
                'total_presencas' => count($this->presenca ?? []),
                'presenca_data' => $this->presenca
            ]);
        } else {
            Log::info('StatusVereadores: PresencaAtualizada ignorado - sessão diferente', [
                'event_sessao_id' => $event['sessaoId'] ?? null,
                'widget_sessao_id' => $this->sessaoAtivaId
            ]);
        }
    }
    
    /**
     * Recarrega apenas os dados de presença
     */
    public function recarregarPresenca(): void
    {
        if (!$this->sessaoAtivaId) return;
        
        $presencas = Presenca::where('sessao_id', $this->sessaoAtivaId)
            ->get();
        
        // CORREÇÃO: Força detecção de mudança no Livewire
        // Limpa completamente o array antes de recarregar
        $this->presenca = [];
        
        // Recarrega com nova referência de array
        $novaPresenca = $presencas->pluck('presente', 'vereador_id')->toArray();
        $this->presenca = $novaPresenca;
        
        // Força atualização do estado do componente
        $this->updateCounter++;
        
        Log::info('StatusVereadores: Presença recarregada', [
            'sessao_id' => $this->sessaoAtivaId,
            'total_presencas' => count($this->presenca),
            'presentes' => array_sum($this->presenca),
            'presenca_data' => $this->presenca,
            'update_counter' => $this->updateCounter
        ]);
    }

    /**
     * Handler principal para evento de voto via WebSocket
     */
    public function handleVotoRegistrado($event): void
    {
        Log::info('StatusVereadores: VotoRegistrado via WebSocket', [
            'event' => $event,
            'pautaEmVotacaoId' => $this->pautaEmVotacaoId
        ]);

        $pautaIdDoVoto = $event['pautaId'] ?? null; 
        $vereadorId = $event['vereadorId'] ?? null;
        $voto = $event['voto'] ?? null;

        // Verifica se é para a pauta atual
        if (!$this->pautaEmVotacaoId || $pautaIdDoVoto != $this->pautaEmVotacaoId) {
            Log::warning('StatusVereadores: Voto ignorado - pauta diferente', [
                'pautaIdDoVoto' => $pautaIdDoVoto,
                'pautaEmVotacaoId' => $this->pautaEmVotacaoId
            ]);
            return;
        }

        if (!$vereadorId || !$voto) {
            Log::warning('StatusVereadores: Voto ignorado - dados incompletos');
            return;
        }

        // MÉTODO 1: Re-atribuição completa do array
        $novosVotos = $this->votos;
        $novosVotos[$vereadorId] = $voto;
        
        // Limpa e re-atribui para forçar detecção de mudança
        $this->votos = [];
        $this->votos = $novosVotos;
        
        // MÉTODO 2: Incrementa contador para forçar re-render
        $this->updateCounter++;
        
        // MÉTODO 3: Dispara refresh explícito
        $this->dispatch('$refresh');
        
        // MÉTODO 4: Recarrega dados do banco como fallback
        $this->recarregarVotosDoBanco();
        
        Log::info('StatusVereadores: Voto processado com sucesso', [
            'vereadorId' => $vereadorId,
            'voto' => $voto,
            'totalVotos' => count($this->votos),
            'updateCounter' => $this->updateCounter
        ]);
    }

    /**
     * Recarrega votos diretamente do banco
     */
    private function recarregarVotosDoBanco(): void
    {
        if ($this->pautaEmVotacaoId) {
            $votosNovos = Voto::where('pauta_id', $this->pautaEmVotacaoId)
                ->pluck('voto', 'vereador_id')
                ->toArray();
            
            $this->votos = [];
            $this->votos = $votosNovos;
            
            Log::info('StatusVereadores: Votos recarregados do banco', [
                'total' => count($this->votos)
            ]);
        }
    }

    /**
     * Handler para evento local (alternativa ao WebSocket)
     */
    public function handleVotoRegistradoLocal($vereadorId, $voto): void
    {
        Log::info('StatusVereadores: VotoRegistradoLocal recebido', [
            'vereadorId' => $vereadorId,
            'voto' => $voto
        ]);
        
        if ($this->pautaEmVotacaoId) {
            $novosVotos = $this->votos;
            $novosVotos[$vereadorId] = $voto;
            $this->votos = [];
            $this->votos = $novosVotos;
            $this->updateCounter++;
        }
    }

    public function handleVotacaoAberta($event): void
    {
        Log::info('StatusVereadores: VotacaoAberta recebido', ['event' => $event]);
        
        $pauta = $event['pauta'] ?? null;
        if ($pauta && ($pauta['sessao_id'] ?? null) == $this->sessaoAtivaId) {
            $this->pautaEmVotacaoId = $pauta['id'] ?? null;
            $this->votos = [];
            $this->updateCounter++;
        }
    }

    public function handleVotacaoEncerrada($event): void
    {
        Log::info('StatusVereadores: VotacaoEncerrada recebido', ['event' => $event]);
        
        $pautaIdEncerrada = $event['pautaId'] ?? null;
        if ($pautaIdEncerrada == $this->pautaEmVotacaoId) {
            // Limpa a votação ativa e os votos para ocultar as informações de votação
            $this->pautaEmVotacaoId = null;
            $this->votos = [];
            $this->updateCounter++;
            
            Log::info('StatusVereadores: Votação encerrada - votos limpos', [
                'pautaIdEncerrada' => $pautaIdEncerrada
            ]);
        }
    }

    /**
     * Método render com tipo de retorno correto
     */
    public function render(): View
    {
        Log::debug('StatusVereadores: Renderizando', [
            'updateCounter' => $this->updateCounter,
            'totalVotos' => count($this->votos),
            'pautaEmVotacaoId' => $this->pautaEmVotacaoId
        ]);
        
        return view($this->view, [
            'sessaoAtivaId' => $this->sessaoAtivaId,
            'pautaEmVotacaoId' => $this->pautaEmVotacaoId,
            'vereadores' => $this->vereadores,
            'presenca' => $this->presenca,
            'votos' => $this->votos,
            'updateCounter' => $this->updateCounter,
        ]);
    }
}