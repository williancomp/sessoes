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

    /**
     * LIVEWIRE 3: Formato correto para Echo listeners (sem ponto antes do evento)
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
        ];
    }

    public function carregarDados(): void
    {
        $sessao = app(EstadoGlobalService::class)->getSessaoAtiva();
        $this->sessaoAtivaId = $sessao['id'] ?? null;

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

        // Usa EstadoGlobalService para obter votação ativa
        $votacaoAtiva = app(EstadoGlobalService::class)->getVotacaoAtiva();
        $this->pautaEmVotacaoId = $votacaoAtiva['pauta_id'] ?? null;

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

        $pautaIdDoVoto = $event['pautaId'] ?? null; 
        $vereadorId = $event['vereadorId'] ?? null;
        $voto = $event['voto'] ?? null;

        // Verifica se o voto é para a pauta atualmente em votação neste widget
        if ($this->pautaEmVotacaoId && $pautaIdDoVoto == $this->pautaEmVotacaoId && $vereadorId && $voto) {
            
            // --- INÍCIO DA CORREÇÃO ---
            // Em vez de modificar o array diretamente ($this->votos[$vereadorId] = $voto;),
            // copiamos, modificamos e re-atribuímos para forçar o Livewire a re-renderizar.
            
            $votosAtuais = $this->votos; // 1. Copia o array
            $votosAtuais[$vereadorId] = $voto; // 2. Modifica a cópia
            $this->votos = $votosAtuais; // 3. Re-atribui. O Livewire agora detecta a mudança.
            // --- FIM DA CORREÇÃO ---

            Log::info('Voto adicionado/atualizado no array e re-atribuído', [
                'pautaId' => $this->pautaEmVotacaoId,
                'vereadorId' => $vereadorId,
                'voto' => $voto
            ]);

            // Esta linha não é mais necessária se você removeu o cache do 'carregarDados',
            // mas pode deixar se quiser.
            $cacheKey = "votos_pauta_{$this->pautaEmVotacaoId}";
            Cache::forget($cacheKey);

        } else {
            Log::warning('Voto recebido ignorado (pauta diferente ou dados ausentes)', ['event' => $event, 'pautaWidget' => $this->pautaEmVotacaoId]);
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

    
}