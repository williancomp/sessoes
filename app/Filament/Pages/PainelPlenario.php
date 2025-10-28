<?php

namespace App\Filament\Pages;

use App\Events\VotacaoAberta;
use App\Events\VotacaoEncerrada;
use App\Models\Pauta;
use Filament\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Arr;

class PainelPlenario extends Page implements HasForms
{
    use InteractsWithForms;

    
    protected string $view = 'filament.pages.painel-plenario';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('abrirVotacao')
                ->label('Abrir Votação')
                ->color('success')
                ->icon('heroicon-o-play')
                ->form([
                    Select::make('pauta_id')
                        ->label('Pauta')
                        ->options(function () {
                            return Pauta::orderBy('ordem')
                                ->get()
                                ->pluck('numero', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $pauta = Pauta::findOrFail(Arr::get($data, 'pauta_id'));
                    $pauta->update(['status' => 'em_votacao']);
                    // Removido o ->toOthers()
                    broadcast(new VotacaoAberta($pauta));
                }),

            Actions\Action::make('encerrarVotacao')
                ->label('Encerrar Votação')
                ->color('danger')
                ->icon('heroicon-o-stop')
                ->form([
                    Select::make('pauta_id')
                        ->label('Pauta')
                        ->options(function () {
                            return Pauta::orderBy('ordem')
                                ->get()
                                ->pluck('numero', 'id');
                        })
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $pauta = Pauta::findOrFail(Arr::get($data, 'pauta_id'));
                    $pauta->update(['status' => 'votada']);
                    // Removido o ->toOthers()
                    broadcast(new VotacaoEncerrada($pauta->id));
                }),
        ];
    }
}
