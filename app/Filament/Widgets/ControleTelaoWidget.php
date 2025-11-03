<?php

namespace App\Filament\Widgets;

use App\Events\LayoutTelaoAlterado;
use App\Events\PalavraEstadoAlterado;
use App\Events\VotacaoAberta;
use App\Events\VotacaoEncerrada;
use App\Models\Pauta;
use App\Models\Sessao;
use App\Models\Vereador;
use App\Services\EstadoGlobalService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ControleTelaoWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.controle-telao-widget';
    protected int | string | array $columnSpan = 1;
    

    public ?int $sessaoAtivaId = null;
    public ?Pauta $pautaEmVotacao = null;
    public ?int $pauta_id_votacao = null;


    // --- NOVAS PROPRIEDADES PARA O CRONÔMETRO ---
    public ?int $vereador_palavra_id = null;
    public ?Vereador $vereadorComPalavra = null;
    public string $palavraStatus = 'stopped'; // 'stopped', 'running', 'paused'
    public int $palavraSegundosRestantes = 0;
    public ?int $palavraTimestampInicio = null;
    
    

    public function mount(): void
    {
        $this->verificarSessaoAtiva();
        $this->atualizarEstadoVotacao();
        $this->atualizarEstadoPalavra();
    }

    
    public function verificarSessaoAtiva(): void
    {
        $sessao = app(EstadoGlobalService::class)->getSessaoAtiva();
        $this->sessaoAtivaId = $sessao['id'] ?? null;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('pauta_id_votacao')
                ->label('Pauta')
                ->options(function () {
                    if (!$this->sessaoAtivaId) {
                        return [];
                    }
                    
                    $cacheKey = "pautas_disponiveis_{$this->sessaoAtivaId}";
                    return Cache::remember($cacheKey, 30, function () {
                        return Pauta::where('sessao_id', $this->sessaoAtivaId)
                            ->whereIn('status', ['aguardando', 'em_discussao', 'em_votacao'])
                            ->orderBy('ordem')
                            ->pluck('numero', 'id');
                    });
                })
                ->required()
                ->searchable()
                ->live(),
        ];
    }

    public function mudarLayoutTelao(string $layout, ?array $dadosPayload = null): void
    {
        $data = null;
        
        if ($layout === 'layout-normal' && isset($dadosPayload['pauta_id'])) {
            // Layout-normal com dados de pauta (antigo layout-pauta)
            $pauta = Pauta::find($dadosPayload['pauta_id']);
            if($pauta) {
                $data = [
                    'id' => $pauta->id,
                    'numero' => $pauta->numero,
                    'descricao' => $pauta->descricao,
                    'autor' => $pauta->autor ?? 'Não informado'
                ];
                
                // CORREÇÃO: Verifica se há vereador falando ativo e preserva no estado
                $estadoGlobal = app(EstadoGlobalService::class)->getEstadoCompleto();
                if ($estadoGlobal['palavra_ativa'] && $estadoGlobal['palavra_ativa']['vereador']) {
                    Log::info('Preservando vereador falando ao exibir pauta', [
                        'pauta' => $pauta->numero,
                        'vereador' => $estadoGlobal['palavra_ativa']['vereador']['nome_parlamentar']
                    ]);
                    // Não adiciona o vereador aos dados, deixa o frontend verificar o estado global
                }
                
                if($pauta->status === 'aguardando') {
                    $pauta->update(['status' => 'em_discussao']);
                }
            }
        } elseif ($layout === 'layout-palavra' && isset($dadosPayload['vereador_id'])) {
            // Layout-palavra é na verdade o layout-normal com overlay do vereador
            $vereador = Vereador::find($dadosPayload['vereador_id']);
            if ($vereador) {
                // CORREÇÃO: Preserva dados da pauta se já houver uma ativa
                $estadoAtual = app(EstadoGlobalService::class)->getTelaoLayout();
                $data = [];
                
                // Se já há dados de pauta no layout atual, preserva
                if (isset($estadoAtual['dados']) && is_array($estadoAtual['dados']) && 
                    isset($estadoAtual['dados']['id']) && !isset($estadoAtual['dados']['vereador'])) {
                    $data = $estadoAtual['dados']; // Preserva dados da pauta
                    Log::info('Preservando dados da pauta ao selecionar vereador', [
                        'pauta' => $data['numero'] ?? 'N/A',
                        'vereador' => $vereador->nome_parlamentar
                    ]);
                }
                
                // Adiciona dados do vereador
                $data['vereador'] = [
                    'id' => $vereador->id,
                    'nome_parlamentar' => $vereador->nome_parlamentar,
                    'partido' => $vereador->partido?->sigla ?? 'S/P'
                ];
                
                // Usa layout-normal mas com dados do vereador para mostrar o overlay
                $layout = 'layout-normal';
            }
        } elseif ($layout === 'layout-voting' && $this->pautaEmVotacao) {
            $data = $this->pautaEmVotacao->toArray();
        }

        // CORREÇÃO: Passa os dados para o setTelaoLayout
        app(EstadoGlobalService::class)->setTelaoLayout($layout, $data);

        broadcast(new LayoutTelaoAlterado($layout, $data))->toOthers();
        Log::info("Layout do telão alterado para: {$layout}", ['data' => $data]);

        Notification::make()
            ->title("Telão atualizado para: " . ucfirst(str_replace('layout-', '', $layout)))
            ->success()
            ->send();
            
        if ($layout === 'layout-voting') {
            $this->atualizarEstadoVotacao();
        }
    }

    
    public function confirmarAbrirVotacao(): void
    {
        $this->validate([
            'pauta_id_votacao' => 'required|exists:pautas,id',
        ]);

        $pautaId = $this->pauta_id_votacao;
        $pauta = Pauta::where('id', $pautaId)
            ->where('sessao_id', $this->sessaoAtivaId)
            ->first();

        if ($pauta && $pauta->status !== 'votada') {
            Pauta::where('sessao_id', $this->sessaoAtivaId)
                ->where('status', 'em_votacao')
                ->where('id', '!=', $pauta->id)
                ->update(['status' => 'votada']);

            // Limpa todos os votos anteriores desta pauta antes de iniciar nova votação
            \App\Models\Voto::where('pauta_id', $pauta->id)->delete();
            Log::info("Votos anteriores da Pauta ID {$pauta->id} foram removidos para nova votação");

            $pauta->update(['status' => 'em_votacao']);
            $this->pautaEmVotacao = $pauta;

            $votacaoData = [
                'pauta_id' => $pauta->id,
                'pauta_numero' => $pauta->numero,
                'pauta_descricao' => $pauta->descricao, 
                'pauta_autor' => $pauta->autor,
                'iniciada_em' => now()->timestamp,
            ];
            app(EstadoGlobalService::class)->setVotacaoAtiva($votacaoData);
            
            // CORREÇÃO: Limpa a contagem de votos no cache para garantir que inicie zerada
            app(EstadoGlobalService::class)->setContagemVotos(['sim' => 0, 'nao' => 0, 'abst' => 0, 'total' => 0]);
            Log::info("Contagem de votos zerada no cache para nova votação da Pauta ID: {$pauta->id}");
            
            $this->mudarLayoutTelao('layout-voting'); // A pauta é pega de $this->pautaEmVotacao

            broadcast(new VotacaoAberta($pauta))->toOthers();
            Log::info("Votação aberta para Pauta ID: {$pauta->id}");
            
            Notification::make()
                ->title("Votação da Pauta {$pauta->numero} aberta!")
                ->success()
                ->send();
                
            $this->atualizarEstadoVotacao();
            
            // ATUALIZADO: Despacha evento para fechar o modal
            $this->dispatch('close-modal', id: 'abrir-votacao');
            $this->pauta_id_votacao = null; // Limpa o select
        } else {
            Notification::make()
                ->title("Não foi possível abrir a votação.")
                ->danger()
                ->send();
        }
    }

    


    public function confirmarEncerrarVotacao(): void
    {
        
        
        if ($this->pautaEmVotacao) {
            $pauta = $this->pautaEmVotacao;
            Log::info("DEBUG: Encerrando votação para pauta ID: {$pauta->id}");
            
            $pauta->update(['status' => 'votada']);

            $pautaIdEncerrada = $pauta->id;
            $this->pautaEmVotacao = null;

            app(EstadoGlobalService::class)->setVotacaoAtiva(null);

            broadcast(new VotacaoEncerrada($pautaIdEncerrada))->toOthers();
            Log::info("Votação encerrada para Pauta ID: {$pautaIdEncerrada}");
            
            Notification::make()
                ->title("Votação da Pauta {$pauta->numero} encerrada!")
                ->success()
                ->send();
                
            $this->atualizarEstadoVotacao();

            // ATUALIZADO: Despacha evento para fechar o modal
            $this->dispatch('close-modal', id: 'confirmar-encerrar-votacao');
        } else {
            
            Notification::make()
                ->title("Nenhuma votação em andamento para encerrar.")
                ->warning()
                ->send();
        }
    }

    protected function getPautasSessaoAtivaOptions(): array
    {
        if (!$this->sessaoAtivaId) return [];
        return Pauta::where('sessao_id', $this->sessaoAtivaId)
            ->orderBy('ordem')
            ->pluck('numero', 'id')
            ->toArray();
    }

    public function getVereadoresOptions(): array
    {
        // Cacheia a lista por 1 minuto para performance
        return Cache::remember('vereadores_options', 60, function () {
            return Vereador::orderBy('nome_parlamentar')
                ->pluck('nome_parlamentar', 'id')
                ->toArray();
        });
    }

    public function atualizarEstadoVotacao(): void
    {
        $votacaoAtiva = app(EstadoGlobalService::class)->getVotacaoAtiva();
        if ($votacaoAtiva) {
            $this->pautaEmVotacao = Pauta::find($votacaoAtiva['pauta_id']);
        } else {
            $this->pautaEmVotacao = null;
        }
    }

    /**
     * Métodos para detectar estados ativos dos botões
     */
    public function isCameraAtiva(): bool
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        $layout = $estado['telao_layout']['layout'] ?? 'layout-normal';
        $dados = $estado['telao_layout']['dados'] ?? null;
        
        return $layout === 'layout-normal' && 
               !$estado['votacao_ativa'] && 
               !$estado['palavra_ativa'] && 
               (!$dados || (!isset($dados['id']) && !isset($dados['vereador'])));
    }

    public function isPautaAtiva(): bool
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        $layout = $estado['telao_layout']['layout'] ?? 'layout-normal';
        $dados = $estado['telao_layout']['dados'] ?? null;
        
        // CORREÇÃO: Pauta está ativa se há dados de pauta, independente de haver vereador
        return $layout === 'layout-normal' && 
               !$estado['votacao_ativa'] && 
               $dados && 
               isset($dados['id']);
    }

    public function isVotacaoAtiva(): bool
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        return (bool) $estado['votacao_ativa'];
    }

    public function isPalavraAtiva(): bool
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        return (bool) $estado['palavra_ativa'];
    }

    public function getPautaAtivaNumero(): ?string
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        $dados = $estado['telao_layout']['dados'] ?? null;
        
        if ($dados && isset($dados['numero'])) {
            return $dados['numero'];
        }
        
        return null;
    }

    public function getVereadorAtivoPalavra(): ?string
    {
        $estado = app(EstadoGlobalService::class)->getEstadoCompleto();
        $palavraAtiva = $estado['palavra_ativa'] ?? null;
        
        if ($palavraAtiva && isset($palavraAtiva['vereador']['nome_parlamentar'])) {
            return $palavraAtiva['vereador']['nome_parlamentar'];
        }
        
        return null;
    }

    /**
     * Carrega o estado atual da palavra (quem está falando)
     */
    public function atualizarEstadoPalavra(): void
    {
        $estado = app(EstadoGlobalService::class)->getPalavraAtiva();
        
        if ($estado) {
            $this->vereador_palavra_id = $estado['vereador']['id'] ?? null;
            $this->vereadorComPalavra = $this->vereador_palavra_id ? Vereador::find($this->vereador_palavra_id) : null;
            $this->palavraStatus = $estado['status'] ?? 'stopped';
            $this->palavraSegundosRestantes = $estado['segundos_restantes'] ?? 0;
            $this->palavraTimestampInicio = $estado['timestamp_inicio'] ?? null;
        } else {
            $this->limparEstadoPalavra();
        }
    }

    /**
     * Limpa o estado da palavra
     */
    private function limparEstadoPalavra(): void
    {
        $this->vereador_palavra_id = null;
        $this->vereadorComPalavra = null;
        $this->palavraStatus = 'stopped';
        $this->palavraSegundosRestantes = 0;
        $this->palavraTimestampInicio = null;
    }

    /**
     * Ação: Define o vereador que vai falar (mas não inicia o tempo)
     */
    public function selecionarVereadorParaPalavra(int $vereadorId): void
    {
        $this->vereador_palavra_id = $vereadorId;
        $this->vereadorComPalavra = Vereador::find($vereadorId);
        $this->palavraStatus = 'selected'; 
        $this->palavraSegundosRestantes = 0;
        $this->palavraTimestampInicio = null;

        // Salva o estado no cache
        $estado = [
            'vereador' => [
                'id' => $this->vereadorComPalavra->id, 
                'nome_parlamentar' => $this->vereadorComPalavra->nome_parlamentar,
                'partido' => ['sigla' => $this->vereadorComPalavra->partido?->sigla ?? 'S/P']
            ],
            'status' => 'selected',
            'segundos_restantes' => 60, // Sempre inicia com 1 minuto
            'timestamp_inicio' => null,
        ];
        app(EstadoGlobalService::class)->setPalavraAtiva($estado);

        // Atualiza o telão para mostrar quem foi selecionado (usa layout-normal com overlay)
        $this->mudarLayoutTelao('layout-palavra', ['vereador_id' => $vereadorId]);
        
        // Notifica outros clientes (outros operadores, se houver)
        broadcast(new PalavraEstadoAlterado(
            'selecionado', 
            $this->vereadorComPalavra, 
            0
        ))->toOthers();
    }

    /**
     * Ação: Inicia o cronômetro para o vereador selecionado
     */
    public function concederPalavra(int $segundos): void
    {
        if (!$this->vereadorComPalavra) return;

        $this->palavraStatus = 'running';
        $this->palavraSegundosRestantes = $segundos;
        $this->palavraTimestampInicio = now()->timestamp;

        $estado = [
            'vereador' => [
                'id' => $this->vereadorComPalavra->id, 
                'nome_parlamentar' => $this->vereadorComPalavra->nome_parlamentar,
                'partido' => ['sigla' => $this->vereadorComPalavra->partido?->sigla ?? 'S/P']
            ],
            'status' => 'running',
            'segundos_restantes' => $this->palavraSegundosRestantes,
            'timestamp_inicio' => $this->palavraTimestampInicio,
        ];
        
        app(EstadoGlobalService::class)->setPalavraAtiva($estado);
        
        broadcast(new PalavraEstadoAlterado(
            'iniciada', 
            $this->vereadorComPalavra, 
            $this->palavraSegundosRestantes,
            $this->palavraTimestampInicio
        ))->toOthers();
    }

    /**
     * Ação: Pausa o cronômetro
     * Recebe o tempo restante do JavaScript
     */
    public function pausarPalavra(int $segundosRestantesJS): void
    {
        if (!$this->vereadorComPalavra || $this->palavraStatus !== 'running') return;

        $this->palavraStatus = 'paused';
        $this->palavraSegundosRestantes = $segundosRestantesJS; // Confia no tempo do cliente
        $this->palavraTimestampInicio = null;

        $estado = [
            'vereador' => [
                'id' => $this->vereadorComPalavra->id, 
                'nome_parlamentar' => $this->vereadorComPalavra->nome_parlamentar,
                'partido' => ['sigla' => $this->vereadorComPalavra->partido?->sigla ?? 'S/P']
            ],
            'status' => 'paused',
            'segundos_restantes' => $this->palavraSegundosRestantes,
            'timestamp_inicio' => null,
        ];
        
        app(EstadoGlobalService::class)->setPalavraAtiva($estado);

        broadcast(new PalavraEstadoAlterado(
            'pausada', 
            $this->vereadorComPalavra, 
            $this->palavraSegundosRestantes
        ))->toOthers();
    }

    /**
     * Ação: Retoma o cronômetro
     */
    public function retomarPalavra(): void
    {
        if (!$this->vereadorComPalavra || $this->palavraStatus !== 'paused') return;

        $this->palavraStatus = 'running';
        $this->palavraTimestampInicio = now()->timestamp; // Novo início

        $estado = [
            'vereador' => [
                'id' => $this->vereadorComPalavra->id, 
                'nome_parlamentar' => $this->vereadorComPalavra->nome_parlamentar,
                'partido' => ['sigla' => $this->vereadorComPalavra->partido?->sigla ?? 'S/P']
            ],
            'status' => 'running',
            'segundos_restantes' => $this->palavraSegundosRestantes,
            'timestamp_inicio' => $this->palavraTimestampInicio,
        ];
        
        app(EstadoGlobalService::class)->setPalavraAtiva($estado);

        broadcast(new PalavraEstadoAlterado(
            'retomada', 
            $this->vereadorComPalavra, 
            $this->palavraSegundosRestantes,
            $this->palavraTimestampInicio
        ))->toOthers();
    }

    /**
     * Ação: Encerra/Cancela a palavra
     */
    public function encerrarPalavra(): void
    {
        $vereadorAnterior = $this->vereadorComPalavra;
        
        app(EstadoGlobalService::class)->setPalavraAtiva(null);
        
        broadcast(new PalavraEstadoAlterado(
            'encerrada', 
            $vereadorAnterior, 
            0
        ))->toOthers();

        $this->limparEstadoPalavra();
        
        // CORREÇÃO: Remove apenas o vereador do layout, preservando a pauta se houver
        $service = app(EstadoGlobalService::class);
        $estadoAtual = $service->getTelaoLayout();
        $dados = $estadoAtual['dados'] ?? [];
        
        // Se há dados de pauta, preserva e remove apenas o vereador
        if (isset($dados['id']) && isset($dados['vereador'])) {
            unset($dados['vereador']);
            $service->setTelaoLayout('layout-normal', $dados);
            
            broadcast(new LayoutTelaoAlterado('layout-normal', $dados))->toOthers();
            
            Log::info('Vereador removido do layout, pauta preservada', [
                'pauta' => $dados['numero'] ?? 'N/A'
            ]);
        }
    }

    protected function getListeners(): array
    {
        return [
            'echo:sessao-plenaria,VotacaoAberta' => 'handleVotacaoAberta',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'handleVotacaoEncerrada',
            'echo:sessao-plenaria,PalavraEstadoAlterado' => 'handlePalavraEstadoAlterado', 
        ];
    }

    public function handleVotacaoAberta($event): void
    {
        Log::info('Widget ControleTelao: VotacaoAberta recebido', ['event' => $event]);
        $this->atualizarEstadoVotacao();
    }

    public function handleVotacaoEncerrada($event): void
    {
        Log::info('Widget ControleTelao: VotacaoEncerrada recebido', ['event' => $event]);
        $this->atualizarEstadoVotacao();
    }

    public function handlePalavraEstadoAlterado($event): void
    {
        Log::info('Widget ControleTelao: PalavraEstadoAlterado recebido', ['event' => $event]);
        $this->atualizarEstadoPalavra();
    }

    
}