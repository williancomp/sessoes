<?php

namespace App\Filament\Widgets;

use App\Events\LayoutTelaoAlterado;
use App\Events\VotacaoAberta;
use App\Events\VotacaoEncerrada;
use App\Models\Pauta;
use App\Models\Sessao;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Arr;
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
    
    // Estados de exibição
    public bool $mostrarFormAbrir = false;
    public bool $mostrarConfirmacaoEncerrar = false;

    public function mount(): void
    {
        $this->sessaoAtivaId = Cache::get('sessao_ativa_id');
        $this->atualizarEstadoVotacao();
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
                    return Pauta::where('sessao_id', $this->sessaoAtivaId)
                        ->whereIn('status', ['aguardando', 'em_discussao', 'em_votacao'])
                        ->orderBy('ordem')
                        ->pluck('numero', 'id');
                })
                ->required()
                ->searchable()
                ->live(),
        ];
    }

    public function mudarLayoutTelao(string $layout, ?int $pautaId = null): void
    {
        $data = null;
        if ($layout === 'layout-pauta' && $pautaId) {
            $pauta = Pauta::find($pautaId);
            if($pauta) {
                $data = $pauta->toArray();
                if($pauta->status === 'aguardando') {
                    $pauta->update(['status' => 'em_discussao']);
                }
            }
        } elseif ($layout === 'layout-votacao' && $this->pautaEmVotacao) {
            $data = $this->pautaEmVotacao->toArray();
        }

        Cache::put('telao_layout', $layout);
        Cache::put('telao_layout_data_pauta_id', $pautaId);

        broadcast(new LayoutTelaoAlterado($layout, $data))->toOthers();
        Log::info("Layout do telão alterado para: {$layout}", ['data' => $data]);

        Notification::make()
            ->title("Telão atualizado para: " . ucfirst(str_replace('layout-', '', $layout)))
            ->success()
            ->send();
            
        if ($layout === 'layout-votacao') {
            $this->atualizarEstadoVotacao();
        }
    }

    // Métodos para controlar exibição do formulário
    public function mostrarFormularioAbrir(): void
    {
        $this->mostrarFormAbrir = true;
        $this->pauta_id_votacao = null;
    }

    public function cancelarAbrirVotacao(): void
    {
        $this->mostrarFormAbrir = false;
        $this->pauta_id_votacao = null;
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
            // Fecha outras votações abertas
            Pauta::where('sessao_id', $this->sessaoAtivaId)
                ->where('status', 'em_votacao')
                ->where('id', '!=', $pauta->id)
                ->update(['status' => 'votada']);

            $pauta->update(['status' => 'em_votacao']);
            $this->pautaEmVotacao = $pauta;

            $this->mudarLayoutTelao('layout-votacao', $pauta->id);

            broadcast(new VotacaoAberta($pauta))->toOthers();
            Log::info("Votação aberta para Pauta ID: {$pauta->id}");
            
            Notification::make()
                ->title("Votação da Pauta {$pauta->numero} aberta!")
                ->success()
                ->send();
                
            $this->atualizarEstadoVotacao();
            $this->mostrarFormAbrir = false;
            $this->pauta_id_votacao = null;
        } else {
            Notification::make()
                ->title("Não foi possível abrir a votação.")
                ->danger()
                ->send();
        }
    }

    // Métodos para controlar confirmação de encerrar
    public function mostrarConfirmacaoEncerrar(): void
    {
        $this->mostrarConfirmacaoEncerrar = true;
    }

    public function cancelarEncerrarVotacao(): void
    {
        $this->mostrarConfirmacaoEncerrar = false;
    }

    public function confirmarEncerrarVotacao(): void
    {
        if ($this->pautaEmVotacao) {
            $pauta = $this->pautaEmVotacao;
            $pauta->update(['status' => 'votada']);

            $pautaIdEncerrada = $pauta->id;
            $this->pautaEmVotacao = null;

            broadcast(new VotacaoEncerrada($pautaIdEncerrada))->toOthers();
            Log::info("Votação encerrada para Pauta ID: {$pautaIdEncerrada}");
            
            Notification::make()
                ->title("Votação da Pauta {$pauta->numero} encerrada!")
                ->success()
                ->send();
                
            $this->atualizarEstadoVotacao();
            $this->mostrarConfirmacaoEncerrar = false;
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

    public function atualizarEstadoVotacao(): void
    {
        if (!$this->sessaoAtivaId) {
            $this->pautaEmVotacao = null;
            return;
        }
        
        $this->pautaEmVotacao = Pauta::where('sessao_id', $this->sessaoAtivaId)
            ->where('status', 'em_votacao')
            ->first();
    }

    protected function getListeners(): array
    {
        return [
            'echo:sessao-plenaria,VotacaoAberta' => 'atualizarEstadoVotacao',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'atualizarEstadoVotacao',
            'sessaoIniciada' => 'atualizarEstadoVotacao',
            'sessaoEncerrada' => 'atualizarEstadoVotacao',
        ];
    }
}