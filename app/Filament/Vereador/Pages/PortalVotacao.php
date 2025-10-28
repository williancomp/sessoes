<?php

namespace App\Filament\Vereador\Pages;

use App\Events\ContagemVotosAtualizada;
use App\Events\VotoRegistrado;
use App\Events\VotacaoAberta;
use App\Events\VotacaoEncerrada;
use App\Models\Pauta; // <--- IMPORTAR
use App\Models\Vereador;
use App\Models\Voto;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class PortalVotacao extends Page
{
    protected string $view = 'filament.vereador.pages.portal-votacao';

    public bool $votacaoAberta = false;
    public ?int $pautaId = null;
    public ?string $pautaNumero = null;

    public int $placarSim = 0;
    public int $placarNao = 0;
    public int $placarAbst = 0;

    /**
     * 1. PERSISTÊNCIA: Isso é executado QUANDO A PÁGINA CARREGA.
     * Ele verifica o estado atual no banco de dados.
     */
    public function mount(): void
    {
        $this->atualizarEstadoPeloBanco();
    }

    /**
     * 2. TEMPO REAL: Isso define os "ouvintes" do Reverb (Echo).
     */
    public function getListeners(): array
    {
        return [
            "echo:sessao-plenaria,VotacaoAberta" => 'onVotacaoAberta',
            "echo:sessao-plenaria,VotacaoEncerrada" => 'onVotacaoEncerrada',
            "echo:sessao-plenaria,ContagemVotosAtualizada" => 'onContagemVotosAtualizada',
        ];
    }

    // --- Handlers de Eventos em Tempo Real ---

    #[On('onVotacaoAberta')]
    public function onVotacaoAberta(array $data): void
    {
        \Log::info('PortalVotacao (PHP): VotacaoAberta recebido', $data);
        $this->votacaoAberta = true;
        $this->pautaId = $data['pauta']['id'] ?? null;
        $this->pautaNumero = $data['pauta']['numero'] ?? '—';
        $this->placarSim = 0;
        $this->placarNao = 0;
        $this->placarAbst = 0;
    }

    #[On('onVotacaoEncerrada')]
    public function onVotacaoEncerrada(array $data): void
    {
        \Log::info('PortalVotacao (PHP): VotacaoEncerrada recebido', $data);
        if (($data['pautaId'] ?? null) === $this->pautaId) {
            $this->votacaoAberta = false;
        }
    }

    #[On('onContagemVotosAtualizada')]
    public function onContagemVotosAtualizada(array $data): void
    {
        \Log::info('PortalVotacao (PHP): ContagemVotosAtualizada recebido', $data);
        if (($data['pautaId'] ?? null) === $this->pautaId) {
            $this->placarSim = $data['sim'] ?? 0;
            $this->placarNao = $data['nao'] ?? 0;
            $this->placarAbst = $data['abst'] ?? 0;
        }
    }

    // --- Ação de Votar ---

    public function registrarVoto(string $voto): void
    {
        if (!$this->votacaoAberta || !$this->pautaId) { /* ... (verificações) ... */ return; }
        $user = Auth::user();
        $vereador = Vereador::where('user_id', $user->id)->first();
        if (!$vereador) { /* ... (verificações) ... */ return; }

        // 1. Registra o voto
        Voto::updateOrCreate(
            ['pauta_id' => $this->pautaId, 'vereador_id' => $vereador->id],
            ['voto' => $voto]
        );

        Notification::make()->title('Voto registrado!')->success()->send();

        // 2. Emite para o TELÃO (nominal)
        broadcast(new VotoRegistrado($vereador, $voto))->toOthers();

        // 3. Calcula
        $sim = Voto::where('pauta_id', $this->pautaId)->where('voto', 'sim')->count();
        $nao = Voto::where('pauta_id', $this->pautaId)->where('voto', 'nao')->count();
        $abst = Voto::where('pauta_id', $this->pautaId)->where('voto', 'abst')->count();

        // 4. Emite para o TELÃO (placar) e OUTROS VEREADORES
        broadcast(new ContagemVotosAtualizada($this->pautaId, $sim, $nao, $abst))->toOthers();

        // 5. ATUALIZA A PRÓPRIA TELA (Instantâneo)
        $this->placarSim = $sim;
        $this->placarNao = $nao;
        $this->placarAbst = $abst;
    }
    
    // --- Função Auxiliar de Persistência ---

    private function atualizarEstadoPeloBanco(): void
    {
        // Encontra a pauta que está ATUALMENTE em votação no banco
        $pautaAtiva = Pauta::where('status', 'em_votacao')->first();

        if ($pautaAtiva) {
            // Se encontrou, configura o estado inicial da página
            $this->votacaoAberta = true;
            $this->pautaId = $pautaAtiva->id;
            $this->pautaNumero = $pautaAtiva->numero;

            // E também carrega o placar ATUAL
            $this->placarSim = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'sim')->count();
            $this->placarNao = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'nao')->count();
            $this->placarAbst = Voto::where('pauta_id', $pautaAtiva->id)->where('voto', 'abst')->count();
        } else {
            // Se não há pauta, garante que o estado esteja limpo
            $this->votacaoAberta = false;
            $this->pautaId = null;
            $this->pautaNumero = null;
            $this->placarSim = 0;
            $this->placarNao = 0;
            $this->placarAbst = 0;
        }
    }
}