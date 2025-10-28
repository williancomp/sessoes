<?php

namespace App\Filament\Resources\Pautas\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;


class PautaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sessao_id')
                    ->relationship('sessao', 'data') // Needs a 'sessao' method in Pauta model
                    ->required(),
                Select::make('tipo_pauta_id')
                    ->relationship('tipoPauta', 'descricao') // Needs a 'tipoPauta' method in Pauta model
                    ->required(),
                TextInput::make('numero')
                    ->label('Número (Ex: PLC 01/2025)')
                    ->required(),
                TextInput::make('autor')
                    ->required(),
                RichEditor::make('descricao')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('ordem')
                    ->label('Ordem na Pauta')
                    ->numeric()
                    ->required()
                    ->default(1),
                Select::make('status')
                    ->options([
                        'aguardando' => 'Aguardando',
                        'em_discussao' => 'Em Discussão',
                        'em_votacao' => 'Em Votação',
                        'votada' => 'Votada',
                    ])
                    ->required()
                    ->default('aguardando'),
            ]);
    }
}
