<?php

namespace App\Filament\Resources\Vereadors;

use App\Filament\Resources\Vereadors\Pages\CreateVereador;
use App\Filament\Resources\Vereadors\Pages\EditVereador;
use App\Filament\Resources\Vereadors\Pages\ListVereadors;
use App\Filament\Resources\Vereadors\Schemas\VereadorForm;
use App\Filament\Resources\Vereadors\Tables\VereadorsTable;
use App\Models\Vereador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VereadorResource extends Resource
{
    protected static ?string $model = Vereador::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return VereadorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VereadorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVereadors::route('/'),
            'create' => CreateVereador::route('/create'),
            'edit' => EditVereador::route('/{record}/edit'),
        ];
    }
}
