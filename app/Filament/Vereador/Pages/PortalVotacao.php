<?php

namespace App\Filament\Vereador\Pages;

use App\Events\ContagemVotosAtualizada;
use App\Events\VotoRegistrado;
use App\Models\Pauta;
use App\Models\Vereador;
use App\Models\Voto;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;


class PortalVotacao extends Page
{
    protected string $view = 'filament.vereador.pages.portal-votacao';

    public bool $votacaoAberta = false;
    public ?int $pautaId = null;
    public ?string $pautaNumero = null;

    public int $placarSim = 0;
    public int $placarNao = 0;
    public int $placarAbst = 0;

    #[On('echo:sessao-plenaria,VotacaoAberta')]
    public function onVotacaoAberta(array $payload): void
    {
        // Acesse a chave 'pauta', que conterá os atributos do modelo serializado
        $pautaPayload = $payload['pauta'] ?? []; // <-- Volte a acessar 'pauta'

        $this->pautaId = $pautaPayload['id'] ?? null;
        $this->pautaNumero = $pautaPayload['numero'] ?? null;
        $this->votacaoAberta = true;

        // Reset placar ao abrir votação
        $this->placarSim = 0;
        $this->placarNao = 0;
        $this->placarAbst = 0;

        // Log para depuração (opcional)
        \Log::info('PortalVotacao received VotacaoAberta (Payload)', $pautaPayload);
    }

    #[On('echo:sessao-plenaria,VotacaoEncerrada')]
    public function onVotacaoEncerrada(array $payload): void
    {
        $encerradaPautaId = $payload['pautaId'] ?? null;
        if ($encerradaPautaId && $encerradaPautaId === $this->pautaId) {
            $this->votacaoAberta = false;
        }
    }

    #[On('echo:sessao-plenaria,ContagemVotosAtualizada')]
    public function onContagemAtualizada(array $payload): void
    {
        if (($payload['pautaId'] ?? null) === $this->pautaId) {
            $this->placarSim = (int) ($payload['sim'] ?? 0);
            $this->placarNao = (int) ($payload['nao'] ?? 0);
            $this->placarAbst = (int) ($payload['abst'] ?? 0);
        }
    }

    public function registrarVoto(string $voto): void
    {
        if (! $this->votacaoAberta || ! in_array($voto, ['sim', 'nao', 'abst'], true) || ! $this->pautaId) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $vereador = Vereador::where('user_id', $user->id)->first();
        if (! $vereador) {
            return;
        }

        // Registra ou atualiza o voto (garante 1 voto por pauta/vereador)
        Voto::updateOrCreate(
            [
                'pauta_id' => $this->pautaId,
                'vereador_id' => $vereador->id,
            ],
            [
                'voto' => $voto,
            ]
        );

        // Emite eventos de voto registrado e atualiza contagem
        broadcast(new VotoRegistrado($vereador, $voto))->toOthers();

        $sim = Voto::where('pauta_id', $this->pautaId)->where('voto', 'sim')->count();
        $nao = Voto::where('pauta_id', $this->pautaId)->where('voto', 'nao')->count();
        $abst = Voto::where('pauta_id', $this->pautaId)->where('voto', 'abst')->count();

        broadcast(new ContagemVotosAtualizada($this->pautaId, $sim, $nao, $abst))->toOthers();

        // Opcional: atualiza placar local
        $this->placarSim = $sim;
        $this->placarNao = $nao;
        $this->placarAbst = $abst;
    }
}