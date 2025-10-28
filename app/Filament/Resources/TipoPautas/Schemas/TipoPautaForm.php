<?php

namespace App\Filament\Resources\TipoPautas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TipoPautaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
