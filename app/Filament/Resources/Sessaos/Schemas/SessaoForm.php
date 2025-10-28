<?php

namespace App\Filament\Resources\Sessaos\Schemas;

use App\Models\Legislatura;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class SessaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('legislatura_id')
                    ->label('Legislatura')
                    ->options(Legislatura::all()->pluck('ano_inicio', 'id')) // Simples, pode melhorar
                    ->default(fn () => Legislatura::where('ativa', true)->first()?->id)
                    ->required(),
                DatePicker::make('data')
                    ->required(),
                Select::make('tipo')
                    ->options([
                        'ordinaria' => 'Ordinária',
                        'extraordinaria' => 'Extraordinária',
                        'solene' => 'Solene',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        'agendada' => 'Agendada',
                        'em_andamento' => 'Em Andamento',
                        'concluida' => 'Concluída',
                    ])
                    ->required()
                    ->default('agendada'),
            ]);
    }
}
