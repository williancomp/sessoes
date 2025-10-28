<?php

namespace App\Filament\Resources\Sessaos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessaosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data')->date('d/m/Y')->sortable(),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agendada' => 'gray',
                        'em_andamento' => 'warning',
                        'concluida' => 'success',
                    }),
            ])
            ->defaultSort('data', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'agendada' => 'Agendada',
                        'em_andamento' => 'Em Andamento',
                        'concluida' => 'Concluída',
                    ])
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
