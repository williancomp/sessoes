<?php

namespace App\Filament\Widgets;

use App\Models\Sessao;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class ControleSessaoWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.controle-sessao-widget';
    protected int | string | array $columnSpan = 'full'; // Ocupa toda a largura

    public ?int $sessao_id_para_iniciar = null;
    public bool $sessaoEstaAtiva = false;
    public ?int $sessaoAtivaId = null;

    public function mount(): void
    {
        $this->verificarSessaoAtiva();
        if (!$this->sessaoEstaAtiva) {
            // Pré-seleciona a sessão agendada mais recente, se houver
            $this->sessao_id_para_iniciar = Sessao::where('status', 'agendada')
                                              ->orderBy('data', 'desc')
                                              ->first()?->id;
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('sessao_id_para_iniciar')
                ->label('Selecione a Sessão para Iniciar')
                ->options(
                    Sessao::where('status', 'agendada')
                          ->orderBy('data', 'desc')
                          ->limit(10) // Limita para não carregar muitas
                          ->get()
                          ->mapWithKeys(fn ($sessao) => [$sessao->id => $sessao->data->format('d/m/Y') . ' - ' . ucfirst($sessao->tipo)])
                          ->toArray()
                )
                ->searchable()
                ->required()
                ->live(), // Atualiza o estado quando selecionado
        ];
    }

    public function iniciarSessao(): void
    {
        $this->validate([
            'sessao_id_para_iniciar' => 'required|exists:sessoes,id',
        ]);

        $sessao = Sessao::find($this->sessao_id_para_iniciar);

        if ($sessao && $sessao->status === 'agendada') {
            // Garante que não haja outra sessão 'em_andamento'
            Sessao::where('status', 'em_andamento')->update(['status' => 'concluida']); // Ou outro status de erro/interrupção

            $sessao->status = 'em_andamento';
            $sessao->save();

            Cache::put('sessao_ativa_id', $sessao->id);
            Cache::put('telao_layout', 'layout-normal'); // Define layout inicial

            Notification::make()
                ->title("Sessão de {$sessao->data->format('d/m/Y')} iniciada!")
                ->success()
                ->send();

            $this->verificarSessaoAtiva(); // Atualiza o estado do widget
            $this->dispatch('sessaoIniciada'); // Notifica a página pai para recarregar widgets

        } else {
            Notification::make()
                ->title('Erro ao iniciar sessão')
                ->body('A sessão selecionada não pôde ser iniciada (pode não estar agendada).')
                ->danger()
                ->send();
        }
    }

    public function encerrarSessao(): void
    {
         $sessao = Sessao::find($this->sessaoAtivaId);
         if ($sessao && $sessao->status === 'em_andamento') {
             $sessao->status = 'concluida';
             $sessao->save();

             Cache::forget('sessao_ativa_id');
             Cache::forget('telao_layout');
             // Pode ser necessário limpar outros caches relacionados à sessão

             // Transmitir evento para o telão voltar ao estado inicial, se necessário
             // broadcast(new LayoutTelaoAlterado('layout-inicial')); // Ou um evento específico de encerramento

             Notification::make()
                ->title("Sessão de {$sessao->data->format('d/m/Y')} encerrada.")
                ->success()
                ->send();

             $this->verificarSessaoAtiva(); // Atualiza o estado do widget
             $this->dispatch('sessaoEncerrada'); // Notifica a página pai

         } else {
             Notification::make()
                ->title('Nenhuma sessão em andamento para encerrar.')
                ->warning()
                ->send();
         }
    }

    public function verificarSessaoAtiva(): void
    {
        $this->sessaoAtivaId = Cache::get('sessao_ativa_id');
        $this->sessaoEstaAtiva = $this->sessaoAtivaId && Sessao::where('id', $this->sessaoAtivaId)->where('status', 'em_andamento')->exists();
    }

    /**
     * Ações que podem ser chamadas pela view
     */
    protected function getActions(): array
    {
        return []; // As ações estão diretamente na view com wire:click
    }
}