<?php

namespace App\Filament\Resources\Sessaos\Pages;

use App\Events\LayoutTelaoAlterado;
use App\Filament\Resources\Sessaos\SessaoResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessao extends EditRecord
{
    protected static string $resource = SessaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // --- Botão de Teste ---
            Action::make('testarTelaoPauta')
                ->label('Testar Telão (Pauta)')
                ->color('warning')
                ->icon('heroicon-o-tv')
                ->action(function () {
                    broadcast(new LayoutTelaoAlterado('layout-pauta'));
                    \Filament\Notifications\Notification::make()
                        ->title('Evento Telão (Pauta) enviado!')
                        ->success()
                        ->send();
                }),
            Action::make('testarTelaoCamera')
                ->label('Testar Telão (Câmera)')
                ->color('info')
                ->icon('heroicon-o-video-camera')
                ->action(function () {
                    broadcast(new LayoutTelaoAlterado('layout-camera'));
                     \Filament\Notifications\Notification::make()
                        ->title('Evento Telão (Câmera) enviado!')
                        ->success()
                        ->send();
                }),
            // --- Fim Botão de Teste ---
        ];
    }
}
