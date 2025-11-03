<?php

namespace App\Filament\Vereador\Pages;

use App\Events\ContagemVotosAtualizada;
use App\Events\VotoRegistrado;
use App\Models\Pauta;
use App\Models\Sessao;
use App\Models\Vereador;
use App\Models\Voto;
use App\Services\EstadoGlobalService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PortalVotacao extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hand-raised';
    protected static ?string $title = 'Portal de Votação';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.vereador.pages.portal-votacao';

    public bool $votacaoAberta = false;
    public ?int $pautaId = null;
    public ?string $pautaNumero = null;

    public int $placarSim = 0;
    public int $placarNao = 0;
    public int $placarAbst = 0;
    
    public bool $processandoVoto = false;
    public ?string $ultimoVoto = null;

    /**
     * PERSISTÊNCIA: Executado quando a página carrega e a cada polling.
     */
    public function mount(): void
    {
        $this->atualizarEstadoPeloBanco();
    }

    protected function getListeners(): array
    {
        return [
            'echo:sessao-plenaria,VotacaoAberta' => 'handleVotacaoAberta',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'handleVotacaoEncerrada',
            'echo:sessao-plenaria,ContagemVotosAtualizada' => 'handleContagemAtualizada',
        ];
    }

    /**
     * Handler para evento de votação aberta
     */
    public function handleVotacaoAberta($event): void
    {
        Log::info('Portal Votação: VotacaoAberta recebido', ['event' => $event]);
        
        $pauta = $event['pauta'] ?? null;
        if ($pauta) {
            $this->votacaoAberta = true;
            $this->pautaId = $pauta['id'] ?? null;
            $this->pautaNumero = $pauta['numero'] ?? '—';
            
            // CORREÇÃO: Sempre zera o placar quando uma nova votação é aberta
            // Isso garante que reinicializações sejam refletidas imediatamente
            $this->placarSim = 0;
            $this->placarNao = 0;
            $this->placarAbst = 0;
            
            // Limpa o último voto para permitir nova votação
            $this->ultimoVoto = null;
            
            Log::info('Portal Votação: Placar zerado para nova votação', [
                'pauta_id' => $this->pautaId,
                'pauta_numero' => $this->pautaNumero
            ]);
        }
    }

    /**
     * Handler para evento de votação encerrada
     */
    public function handleVotacaoEncerrada($event): void
    {
        Log::info('Portal Votação: VotacaoEncerrada recebido', ['event' => $event]);
        
        $pautaIdEncerrada = $event['pautaId'] ?? null;
        if ($pautaIdEncerrada === $this->pautaId) {
            $this->votacaoAberta = false;
            $this->pautaId = null;
            $this->pautaNumero = null;
        }
    }

    /**
     * Handler para atualização de contagem
     */
    public function handleContagemAtualizada($event): void
    {
        Log::info('Portal Votação: ContagemVotosAtualizada recebido', ['event' => $event]);
        
        $pautaId = $event['pautaId'] ?? null;
        if ($pautaId === $this->pautaId) {
            $this->placarSim = $event['sim'] ?? 0;
            $this->placarNao = $event['nao'] ?? 0;
            $this->placarAbst = $event['abst'] ?? 0;
        }
    }

    /**
     * Ação de registrar voto com feedback visual aprimorado
     */
    public function registrarVoto(string $voto): void
    {
        Log::info('PortalVotacao: Iniciando registro de voto', ['voto' => $voto]);
        
        // Previne múltiplos cliques
        if ($this->processandoVoto) {
            Log::warning('PortalVotacao: Voto já em processamento');
            return;
        }

        $this->processandoVoto = true;

        try {
            // Validações
            if (!$this->votacaoAberta || !$this->pautaId) {
                Log::warning('PortalVotacao: Tentativa de voto sem votação aberta');
                Notification::make()
                    ->title('Não há votação aberta no momento.')
                    ->warning()
                    ->duration(3000)
                    ->send();
                return;
            }

            if (!in_array($voto, ['sim', 'nao', 'abst'])) {
                Log::error('PortalVotacao: Voto inválido', ['voto' => $voto]);
                Notification::make()
                    ->title('Voto inválido.')
                    ->danger()
                    ->duration(3000)
                    ->send();
                return;
            }

            $user = Auth::user();
            $vereador = Vereador::where('user_id', $user->id)->first();
            
            if (!$vereador) {
                Log::error('PortalVotacao: Vereador não encontrado', ['user_id' => $user->id]);
                Notification::make()
                    ->title('Erro: Vereador não encontrado.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Verifica se já votou (para feedback)
            $votoAnterior = Voto::where('pauta_id', $this->pautaId)
                ->where('vereador_id', $vereador->id)
                ->first();

            // 1. Registra o voto no banco
            Voto::updateOrCreate(
                ['pauta_id' => $this->pautaId, 'vereador_id' => $vereador->id],
                ['voto' => $voto]
            );

            Log::info('PortalVotacao: Voto registrado no banco', [
                'vereador_id' => $vereador->id,
                'pauta_id' => $this->pautaId,
                'voto' => $voto
            ]);

            $this->ultimoVoto = $voto;

            // Feedback personalizado
            $mensagem = $votoAnterior 
                ? "Voto alterado para: " . strtoupper($voto)
                : "Voto registrado: " . strtoupper($voto);

            Notification::make()
                ->title($mensagem)
                ->success()
                ->duration(2000)
                ->send();

            // 2. Emite evento VotoRegistrado para o telão e outros componentes
            $eventoVoto = new VotoRegistrado($vereador, $voto, $this->pautaId);
            
            // Broadcast para WebSocket (telão e outros clientes)
            broadcast($eventoVoto)->toOthers();
            
            Log::info('PortalVotacao: Evento VotoRegistrado emitido', [
                'vereador_id' => $vereador->id,
                'voto' => $voto,
                'pauta_id' => $this->pautaId
            ]);

            // 3. Calcula e atualiza contagem
            $sim = Voto::where('pauta_id', $this->pautaId)->where('voto', 'sim')->count();
            $nao = Voto::where('pauta_id', $this->pautaId)->where('voto', 'nao')->count();
            $abst = Voto::where('pauta_id', $this->pautaId)->where('voto', 'abst')->count();
            
            $contagem = ['sim' => $sim, 'nao' => $nao, 'abst' => $abst];
            
            // Atualiza estado global
            app(EstadoGlobalService::class)->setContagemVotos($contagem);

            // 4. Emite evento de contagem atualizada
            $eventoContagem = new ContagemVotosAtualizada(
                $this->pautaId, 
                $contagem['sim'], 
                $contagem['nao'], 
                $contagem['abst']
            );
            
            broadcast($eventoContagem)->toOthers();
            
            Log::info('PortalVotacao: Evento ContagemVotosAtualizada emitido', [
                'contagem' => $contagem
            ]);

            // 5. Atualiza a própria tela
            $this->placarSim = $contagem['sim'];
            $this->placarNao = $contagem['nao'];
            $this->placarAbst = $contagem['abst'];

        } catch (\Exception $e) {
            Log::error('PortalVotacao: Erro ao registrar voto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'voto' => $voto,
                'pauta_id' => $this->pautaId
            ]);

            Notification::make()
                ->title('Erro ao registrar voto')
                ->body('Tente novamente em alguns instantes.')
                ->danger()
                ->duration(5000)
                ->send();
        } finally {
            $this->processandoVoto = false;
        }
    }
    
    /**
     * Atualiza estado baseado no banco de dados
     * Chamado no mount() e no polling
     */
    public function atualizarEstadoPeloBanco(): void
    {
        // Usa o EstadoGlobalService para obter votação ativa
        $votacaoAtiva = app(EstadoGlobalService::class)->getVotacaoAtiva();

        if ($votacaoAtiva) {
            $this->votacaoAberta = true;
            $this->pautaId = $votacaoAtiva['pauta_id'];
            $this->pautaNumero = $votacaoAtiva['pauta_numero'];

            // Carrega placar atual do estado global
            $contagem = app(EstadoGlobalService::class)->getContagemVotos();
            $this->placarSim = $contagem['sim'];
            $this->placarNao = $contagem['nao'];
            $this->placarAbst = $contagem['abst'];
        } else {
            // Limpa estado
            $this->votacaoAberta = false;
            $this->pautaId = null;
            $this->pautaNumero = null;
            $this->placarSim = 0;
            $this->placarNao = 0;
            $this->placarAbst = 0;
        }
    }
}