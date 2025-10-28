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
use Illuminate\Support\Facades\Log;

class StatusVereadoresWidget extends Widget
{
    protected string $view = 'filament.widgets.status-vereadores-widget';
    protected int | string | array $columnSpan = 2;
    protected static bool $isLazy = false;

    public ?int $sessaoAtivaId = null;
    public ?int $pautaEmVotacaoId = null;
    public Collection $vereadores;
    public array $presenca = [];
    public array $votos = [];

    public function mount(): void
    {
        $this->vereadores = new Collection();
        $this->carregarDados();
    }

    protected function getListeners(): array
    {
        return [
            'echo:sessao-plenaria,PresencaAtualizada' => 'handlePresencaAtualizada',
            'echo:sessao-plenaria,VotoRegistrado' => 'handleVotoRegistrado',
            'echo:sessao-plenaria,VotacaoAberta' => 'handleVotacaoAberta',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'handleVotacaoEncerrada',
            'sessaoIniciada' => 'carregarDados',
            'sessaoEncerrada' => 'limparDados',
        ];
    }

    public function carregarDados(): void
    {
        $this->sessaoAtivaId = Cache::get('sessao_ativa_id');

        if (!$this->sessaoAtivaId) {
            $this->limparDados();
            return;
        }

        Log::info('Carregando dados do widget StatusVereadores', [
            'sessao_id' => $this->sessaoAtivaId
        ]);

        // Carrega todos os vereadores
        $this->vereadores = Vereador::with('partido')->orderBy('nome_parlamentar')->get();

        // Carrega presença inicial
        $presencas = Presenca::where('sessao_id', $this->sessaoAtivaId)
            ->get();
        
        $this->presenca = $presencas->pluck('presente', 'vereador_id')->toArray();

        Log::info('Presenças carregadas', [
            'total' => count($this->presenca),
            'presencas' => $this->presenca
        ]);

        // Verifica se há pauta em votação
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

    public function limparDados(): void
    {
        $this->sessaoAtivaId = null;
        $this->pautaEmVotacaoId = null;
        $this->vereadores = new Collection();
        $this->presenca = [];
        $this->votos = [];
    }

    // --- Handlers de Eventos ---

    public function handlePresencaAtualizada($event): void
    {
        Log::info('Widget StatusVereadores: PresencaAtualizada recebido', ['event' => $event]);
        
        if (($event['sessaoId'] ?? null) == $this->sessaoAtivaId) {
            // Recarrega presenças
            $this->presenca = Presenca::where('sessao_id', $this->sessaoAtivaId)
                ->pluck('presente', 'vereador_id')
                ->toArray();
                
            Log::info('Presenças atualizadas via evento', [
                'total' => count($this->presenca),
                'presencas' => $this->presenca
            ]);
        }
    }

    public function handleVotoRegistrado($event): void
    {
        Log::info('Widget StatusVereadores: VotoRegistrado recebido', ['event' => $event]);
        
        if ($this->pautaEmVotacaoId) {
            $vereadorId = $event['vereadorId'] ?? $event['vereador_id'] ?? null;
            $voto = $event['voto'] ?? null;
            
            if ($vereadorId && $voto) {
                $this->votos[$vereadorId] = $voto;
            }
        }
    }

    public function handleVotacaoAberta($event): void
    {
        Log::info('Widget StatusVereadores: VotacaoAberta recebido', ['event' => $event]);
        
        $pauta = $event['pauta'] ?? null;
        if ($pauta && ($pauta['sessao_id'] ?? null) == $this->sessaoAtivaId) {
            $this->pautaEmVotacaoId = $pauta['id'] ?? null;
            $this->votos = [];
        }
    }

    public function handleVotacaoEncerrada($event): void
    {
        Log::info('Widget StatusVereadores: VotacaoEncerrada recebido', ['event' => $event]);
        
        $pautaIdEncerrada = $event['pautaId'] ?? null;
        if ($pautaIdEncerrada == $this->pautaEmVotacaoId) {
            $this->pautaEmVotacaoId = null;
            // Mantém votos visíveis após encerramento
        }
    }

    /**
     * Polling como fallback
     */
    public function getPollingInterval(): ?string
    {
        return '15s';
    }
}