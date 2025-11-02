<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ControleSessaoWidget;
use App\Filament\Widgets\ControleTelaoWidget;
use App\Filament\Widgets\StatusVereadoresWidget;
use App\Models\Sessao;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache; // Importar Cache

class PainelOperador extends Page
{
    //protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected  string $view = 'filament.pages.painel-operador';
    protected static ?string $title = 'Painel do Operador de Sessão';
    protected static ?string $slug = 'painel-operador';

    // Define se a visualização principal do operador deve ser exibida
    public bool $sessaoAtiva = false;

    public function mount(): void
    {
        $this->verificarSessaoAtiva();
    }

    /**
     * Verifica se existe uma sessão em andamento no Cache ou DB
     * e atualiza a propriedade $sessaoAtiva.
     */
    public function verificarSessaoAtiva(): void
    {
        $sessaoId = Cache::get('sessao_ativa_id');
        if ($sessaoId) {
            $sessao = Sessao::where('id', $sessaoId)->where('status', 'em_andamento')->first();
            $this->sessaoAtiva = (bool) $sessao;
        } else {
             // Fallback: Verifica se há alguma sessão em andamento no DB
             $sessao = Sessao::where('status', 'em_andamento')->first();
             if ($sessao) {
                 Cache::put('sessao_ativa_id', $sessao->id); // Atualiza o cache
                 $this->sessaoAtiva = true;
             } else {
                 $this->sessaoAtiva = false;
             }
        }
    }

    /**
     * Define quais widgets são carregados condicionalmente.
     */
    protected function getWidgets(): array
    {
        $this->verificarSessaoAtiva(); // Re-verifica antes de decidir quais widgets mostrar

        if ($this->sessaoAtiva) {
            // Widgets para quando a sessão está ativa
            return [
                ControleTelaoWidget::class,   // Ocupará 1 coluna
                StatusVereadoresWidget::class, // Ocupará 2 colunas (configurado no widget)
            ];
        } else {
            // Widget para quando NENHUMA sessão está ativa
            return [
                ControleSessaoWidget::class, // Ocupará largura total (configurado na view)
            ];
        }
    }

    /**
     * Define o layout de colunas para os widgets quando a sessão está ativa.
     */
    protected function getColumns(): int | string | array
    {
        return $this->sessaoAtiva ? 3 : 1; // 3 colunas se ativa, 1 se inativa
    }

    // Listener para atualizar a view da página quando a sessão muda
    protected function getListeners(): array
    {
        return [
            'sessaoIniciada' => 'verificarSessaoAtiva',
            'sessaoEncerrada' => 'verificarSessaoAtiva',
        ];
    }

   
}