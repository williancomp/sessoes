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
// --- Adicionar ---
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action; // Importar Action

// --- Implementar HasActions ---
class ControleTelaoWidget extends Widget implements HasForms, HasActions
{
    // --- Adicionar Trait ---
    use InteractsWithForms, InteractsWithActions;

    protected string $view = 'filament.widgets.controle-telao-widget';
    protected int | string | array $columnSpan = 1;

    public ?int $sessaoAtivaId = null;
    public ?Pauta $pautaEmVotacao = null;
    public ?Pauta $pautaParaVotar = null;
    public ?int $pauta_id_votacao = null;

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
        // ... (código existente sem alterações) ...
        $data = null;
        if ($layout === 'layout-pauta' && $pautaId) {
            $pauta = Pauta::find($pautaId);
            if($pauta) {
                $data = $pauta->toArray(); // Envia dados da pauta
                if($pauta->status === 'aguardando') {
                    $pauta->update(['status' => 'em_discussao']);
                }
            }
        }
         elseif ($layout === 'layout-votacao' && $this->pautaEmVotacao) {
            $data = $this->pautaEmVotacao->toArray();
        }

        Cache::put('telao_layout', $layout);
        Cache::put('telao_layout_data_pauta_id', $pautaId);

        broadcast(new LayoutTelaoAlterado($layout, $data))->toOthers();
        Log::info("Layout do telão alterado para: {$layout}", ['data' => $data]);

        Notification::make()->title("Telão atualizado para: " . ucfirst(str_replace('layout-', '', $layout)))->success()->send();
        // Atualiza estado interno se mudou para votação via botão
         if ($layout === 'layout-votacao') {
             $this->atualizarEstadoVotacao();
         }
    }

    // --- Mudar de abrirVotacaoAction() para Action::make() ---
    public function abrirVotacaoAction(): Action
    {
        // Renomear o handle para algo único se necessário, ou manter 'abrirVotacao'
        return Action::make('abrirVotacaoModalAction') // Nome único para a Action
            ->label('Abrir Votação')
            ->color('success')
            ->icon('heroicon-o-play')
            ->form($this->getFormSchema())
            ->action(function (array $data): void {
                // Lógica para abrir votação (sem alterações)
                // ... (código existente) ...
                $pautaId = Arr::get($data, 'pauta_id_votacao');
                $pauta = Pauta::where('id', $pautaId)
                              ->where('sessao_id', $this->sessaoAtivaId) // Garante que é da sessão ativa
                              ->first();

                if ($pauta && $pauta->status !== 'votada') {
                    Pauta::where('sessao_id', $this->sessaoAtivaId)
                         ->where('status', 'em_votacao')
                         ->where('id', '!=', $pauta->id)
                         ->update(['status' => 'votada']);

                    $pauta->update(['status' => 'em_votacao']);
                    $this->pautaEmVotacao = $pauta;

                    $this->mudarLayoutTelao('layout-votacao', $pauta->id);

                    broadcast(new VotacaoAberta($pauta))->toOthers();
                    Log::info("Votação aberta para Pauta ID: {$pauta->id}");
                    Notification::make()->title("Votação da Pauta {$pauta->numero} aberta!")->success()->send();
                     $this->atualizarEstadoVotacao(); // Garante que o estado do widget (e botão encerrar) atualize
                } else {
                    Notification::make()->title("Não foi possível abrir a votação.")->danger()->send();
                }
            });
    }

    // --- Mudar de encerrarVotacaoAction() para Action::make() ---
    public function encerrarVotacaoAction(): Action
    {
         // Renomear o handle para algo único se necessário
        return Action::make('encerrarVotacaoModalAction') // Nome único para a Action
            ->label('Encerrar Votação Atual')
            ->color('danger')
            ->icon('heroicon-o-stop')
            ->requiresConfirmation()
            ->action(function (): void {
                // Lógica para encerrar votação (sem alterações)
                 // ... (código existente) ...
                if ($this->pautaEmVotacao) {
                    $pauta = $this->pautaEmVotacao;
                    $pauta->update(['status' => 'votada']);

                    $pautaIdEncerrada = $pauta->id;
                    $this->pautaEmVotacao = null;

                    broadcast(new VotacaoEncerrada($pautaIdEncerrada))->toOthers();
                    Log::info("Votação encerrada para Pauta ID: {$pautaIdEncerrada}");
                    Notification::make()->title("Votação da Pauta {$pauta->numero} encerrada!")->success()->send();
                     $this->atualizarEstadoVotacao(); // Garante atualização do estado
                } else {
                     Notification::make()->title("Nenhuma votação em andamento para encerrar.")->warning()->send();
                }
            })
            ->disabled(!$this->pautaEmVotacao);
    }

    protected function getPautasSessaoAtivaOptions(): array
    {
        // ... (código existente sem alterações) ...
         if (!$this->sessaoAtivaId) return [];
        return Pauta::where('sessao_id', $this->sessaoAtivaId)
                  ->orderBy('ordem')
                  ->pluck('numero', 'id')
                  ->toArray();
    }

    // #[On('atualizarEstadoVotacao')] // Pode ser chamado via dispatch ou listener
    public function atualizarEstadoVotacao(): void
    {
         if (!$this->sessaoAtivaId) {
            $this->pautaEmVotacao = null;
            return;
        }
        // Recarrega do banco para garantir consistência
        $this->pautaEmVotacao = Pauta::where('sessao_id', $this->sessaoAtivaId)
                                     ->where('status', 'em_votacao')
                                     ->first();
         // Precisamos reavaliar a action de encerrar aqui
         // No entanto, modificar actions dinamicamente fora do ciclo de vida pode ser complexo.
         // A forma mais simples é deixar o ->disabled() na definição da action reavaliar no próximo render.
    }

    protected function getListeners(): array
    {
        // Atualiza o estado da votação quando eventos relevantes ocorrem
        return [
            'echo:sessao-plenaria,VotacaoAberta' => 'atualizarEstadoVotacao',
            'echo:sessao-plenaria,VotacaoEncerrada' => 'atualizarEstadoVotacao',
             'sessaoIniciada' => 'atualizarEstadoVotacao', // Atualiza se a sessão mudar
             'sessaoEncerrada' => 'atualizarEstadoVotacao', // Limpa o estado
        ];
    }

    // Remover getMountedActions(), pois as actions serão renderizadas diretamente na view
    // protected function getMountedActions(): array { ... }
}