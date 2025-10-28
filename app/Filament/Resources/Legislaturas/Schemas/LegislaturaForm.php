<?php

namespace App\Filament\Resources\Legislaturas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LegislaturaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ano_inicio')
                    ->label('Ano de Início')
                    ->required()
                    ->numeric()
                    ->minLength(4)
                    ->maxLength(4),
                TextInput::make('ano_fim')
                    ->label('Ano de Fim')
                    ->required()
                    ->numeric()
                    ->minLength(4)
                    ->maxLength(4),
                Toggle::make('ativa')
                    ->label('Legislatura Ativa?')
                    ->required(),
            ]);
    }
}
