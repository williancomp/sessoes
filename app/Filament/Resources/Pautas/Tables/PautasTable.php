<?php

namespace App\Filament\Resources\Pautas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PautasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ordem')->sortable(),
                TextColumn::make('numero')->searchable(),
                TextColumn::make('autor')->searchable(),
                TextColumn::make('sessao.data')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aguardando' => 'gray',
                        'em_discussao' => 'info',
                        'em_votacao' => 'warning',
                        'votada' => 'success',
                    }),
            ])
            ->defaultSort('ordem', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
