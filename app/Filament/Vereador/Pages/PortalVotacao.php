<?php

namespace App\Filament\Vereador\Pages;

use App\Events\ContagemVotosAtualizada;
use App\Events\VotoRegistrado;
use App\Models\Pauta;
use App\Models\Vereador;
use App\Models\Voto;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PortalVotacao extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    protected string $view = 'filament.vereador.pages.portal-votacao';
    protected static ?string $title = 'Portal de Votação';

    public bool $votacaoAberta = false;
    public ?int $pautaId = null;
    public ?string $pautaNumero = null;

    public int $placarSim = 0;
    public int $placarNao = 0;
    public int $placarAbst = 0;

    /**
     * PERSISTÊNCIA: Executado quando a página carrega e a cada polling.
     * Verifica o estado atual no banco de dados.
     */
    public function mount(): void
    {
        $this->atualizarEstadoPeloBanco();
    }

    /**
     * TEMPO REAL: Define os listeners do Echo/Reverb
     */
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
            $this->placarSim = 0;
            $this->placarNao = 0;
            $this->placarAbst = 0;
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
     * Ação de registrar voto
     */
    public function registrarVoto(string $voto): void
    {
        // Validações
        if (!$this->votacaoAberta || !$this->pautaId) {
            Notification::make()
                ->title('Não há votação aberta no momento.')
                ->warning()
                ->send();
            return;
        }

        if (!in_array($voto, ['sim', 'nao', 'abst'])) {
            Notification::make()
                ->title('Voto inválido.')
                ->danger()
                ->send();
            return;
        }

        $user = Auth::user();
        $vereador = Vereador::where('user_id', $user->id)->first();
        
        if (!$vereador) {
            Notification::make()
                ->title('Erro: Vereador não encontrado.')
                ->danger()
                ->send();
            return;
        }

        // 1. Registra o voto
        Voto::updateOrCreate(
            ['pauta_id' => $this->pautaId, 'vereador_id' => $vereador->id],
            ['voto' => $voto]
        );

        Log::info('Voto registrado', [
            'vereador_id' => $vereador->id,
            'pauta_id' => $this->pautaId,
            'voto' => $voto
        ]);

        Notification::make()
            ->title('Voto registrado com sucesso!')
            ->success()
            ->send();

        // 2. Emite evento para o telão (nominal)
        broadcast(new VotoRegistrado($vereador, $voto))->toOthers();

        // 3. Calcula totais
        $sim = Voto::where('pauta_id', $this->pautaId)->where('voto', 'sim')->count();
        $nao = Voto::where('pauta_id', $this->pautaId)->where('voto', 'nao')->count();
        $abst = Voto::where('pauta_id', $this->pautaId)->where('voto', 'abst')->count();

        // 4. Emite contagem atualizada
        broadcast(new ContagemVotosAtualizada($this->pautaId, $sim, $nao, $abst))->toOthers();

        // 5. Atualiza a própria tela instantaneamente
        $this->placarSim = $sim;
        $this->placarNao = $nao;
        $this->placarAbst = $abst;
    }
    
    /**
     * Atualiza estado baseado no banco de dados
     * Chamado no mount() e no polling
     */
    public function atualizarEstadoPeloBanco(): void
    {
        // Encontra pauta em votação
        $pautaAtiva = Pauta::where('status', 'em_votacao')->first();

        if ($pautaAtiva) {
            $this->votacaoAberta = true;
            $this->pautaId = $pautaAtiva->id;
            $this->pautaNumero = $pautaAtiva->numero;

            // Carrega placar atual
            $this->placarSim = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'sim')->count();
            $this->placarNao = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'nao')->count();
            $this->placarAbst = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'abst')->count();
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