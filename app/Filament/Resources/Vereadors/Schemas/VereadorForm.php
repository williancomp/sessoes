<?php

namespace App\Filament\Resources\Vereadors\Schemas;

use App\Models\Partido;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VereadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Vereador')
                    ->schema([
                        TextInput::make('nome_parlamentar')
                            ->required()
                            ->maxLength(255),
                        Select::make('partido_id')
                            ->label('Partido')
                            ->options(Partido::all()->pluck('sigla', 'id'))
                            ->searchable()
                            ->required(),
                        FileUpload::make('foto')
                            ->image()
                            ->directory('fotos-vereadores')
                            ->imageEditor(), // Adiciona um editor de imagem
                        TextInput::make('identificador_microfone')
                            ->label('ID do Microfone')
                            ->helperText('Ex: Canal_5 ou 1')
                            ->nullable(),
                    ])->columns(2),
                
                Section::make('Conta de Acesso (Tablet)')
                    ->description('Cria o usuário para o vereador acessar o /portal.')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            // ->dehydrated(false) foi REMOVIDO
                            ->disabled(fn (string $operation): bool => $operation === 'edit'),
                        
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8),
                            // ->dehydrated(false) foi REMOVIDO
                    ])->columns(2)
                    ->visible(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
