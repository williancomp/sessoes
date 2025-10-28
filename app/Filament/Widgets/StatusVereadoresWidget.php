<?php

namespace App\Filament\Widgets;

use App\Models\Pauta;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\Vereador;
use App\Models\Voto;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class StatusVereadoresWidget extends Widget
{
    protected string $view = 'filament.widgets.status-vereadores-widget';
    protected int | string | array $columnSpan = 2; // Ocupa 2 colunas em um layout de 3
    protected static bool $isLazy = false; // Carrega imediatamente

    public ?int $sessaoAtivaId = null;
    public ?int $pautaEmVotacaoId = null;
    public Collection $vereadores;
    public array $presenca = []; // [vereador_id => true/false]
    public array $votos = []; // [vereador_id => 'sim'/'nao'/'abst'/null]

    protected $listeners = [
        'echo:sessao-plenaria,PresencaAtualizada' => 'atualizarPresencaVereador',
        'echo:sessao-plenaria,VotoRegistrado' => 'atualizarVotoVereador',
        'echo:sessao-plenaria,VotacaoAberta' => 'handleVotacaoAberta',
        'echo:sessao-plenaria,VotacaoEncerrada' => 'handleVotacaoEncerrada',
        'sessaoIniciada' => 'carregarDados', // Ouve evento da página pai
        'sessaoEncerrada' => 'limparDados', // Ouve evento da página pai
    ];

    public function mount(): void
    {
        $this->carregarDados();
    }

    #[On('carregarDados')] // Permite ser chamado por dispatch
    public function carregarDados(): void
    {
        $this->sessaoAtivaId = Cache::get('sessao_ativa_id');

        if (!$this->sessaoAtivaId) {
            $this->vereadores = new Collection();
            $this->presenca = [];
            $this->votos = [];
            $this->pautaEmVotacaoId = null;
            return;
        }

        // Carrega todos os vereadores (pode otimizar se tiver muitos)
        $this->vereadores = Vereador::with('partido')->orderBy('nome_parlamentar')->get();

        // Carrega presença inicial
        $this->presenca = Presenca::where('sessao_id', $this->sessaoAtivaId)
                                  ->pluck('presente', 'vereador_id')
                                  ->toArray();

        // Verifica se há pauta em votação e carrega votos iniciais
         $pautaEmVotacao = Pauta::where('sessao_id', $this->sessaoAtivaId)
                                ->where('status', 'em_votacao')
                                ->first();
        $this->pautaEmVotacaoId = $pautaEmVotacao?->id;

        if ($this->pautaEmVotacaoId) {
            $this->votos = Voto::where('pauta_id', $this->pautaEmVotacaoId)
                               ->pluck('voto', 'vereador_id')
                               ->toArray();
        } else {
            $this->votos = [];
        }
    }

    #[On('limparDados')] // Limpa os dados quando a sessão é encerrada
    public function limparDados(): void
    {
         $this->sessaoAtivaId = null;
         $this->pautaEmVotacaoId = null;
         $this->vereadores = new Collection();
         $this->presenca = [];
         $this->votos = [];
    }

    // --- Handlers de Eventos Echo ---

    #[On('atualizarPresencaVereador')]
    public function atualizarPresencaVereador(array $event): void
    {
        // O evento PresencaAtualizada não traz o ID do vereador individualmente.
        // Precisamos recarregar a lista de presença para a sessão ativa.
        // Otimização: O evento poderia ser modificado para incluir o vereador_id e o status.
         if ($event['sessaoId'] ?? null == $this->sessaoAtivaId) {
             $this->presenca = Presenca::where('sessao_id', $this->sessaoAtivaId)
                                   ->pluck('presente', 'vereador_id')
                                   ->toArray();
             // $this->skipRender(); // Pode usar se a view só acessa $presenca
         }
    }

     #[On('atualizarVotoVereador')]
    public function atualizarVotoVereador(array $event): void
    {
         // Assume que VotoRegistrado tem 'vereadorId' e 'voto'
         // Verifica se o voto é para a pauta atualmente em votação neste widget
         if ($this->pautaEmVotacaoId) { // Só atualiza se uma votação estiver ativa aqui
             $vereadorId = $event['vereadorId'] ?? $event['vereador_id'] ?? null;
             $voto = $event['voto'] ?? null;
             if ($vereadorId && $voto) {
                 $this->votos[$vereadorId] = $voto;
                 // $this->skipRender();
             }
         }
    }

    #[On('handleVotacaoAberta')]
    public function handleVotacaoAberta(array $event): void
    {
         $pauta = $event['pauta'] ?? null;
         if ($pauta && $pauta['sessao_id'] == $this->sessaoAtivaId) {
             $this->pautaEmVotacaoId = $pauta['id'];
             $this->votos = []; // Limpa votos anteriores ao abrir uma nova votação
         }
    }

    #[On('handleVotacaoEncerrada')]
    public function handleVotacaoEncerrada(array $event): void
    {
        $pautaIdEncerrada = $event['pautaId'] ?? null;
         if ($pautaIdEncerrada == $this->pautaEmVotacaoId) {
             $this->pautaEmVotacaoId = null;
             // Não limpa os votos aqui, para que o último estado fique visível
             // A limpeza ocorrerá na próxima VotacaoAberta
         }
    }

    /**
     * Força a atualização do widget a cada 15 segundos como fallback.
     */
    public function getPollingInterval(): ?string
    {
        return '15s';
    }

}