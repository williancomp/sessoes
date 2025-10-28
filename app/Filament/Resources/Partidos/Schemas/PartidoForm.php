<?php

namespace App\Filament\Resources\Partidos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PartidoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sigla')
                    ->required()
                    ->maxLength(50),
            ]);
    }
}
