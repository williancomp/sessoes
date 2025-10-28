<?php

namespace App\Filament\Resources\Sessaos;

use App\Filament\Resources\Sessaos\Pages\CreateSessao;
use App\Filament\Resources\Sessaos\Pages\EditSessao;
use App\Filament\Resources\Sessaos\Pages\ListSessaos;
use App\Filament\Resources\Sessaos\RelationManagers\PautasRelationManager;
use App\Filament\Resources\Sessaos\Schemas\SessaoForm;
use App\Filament\Resources\Sessaos\Tables\SessaosTable;
use App\Models\Sessao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SessaoResource extends Resource
{
    protected static ?string $model = Sessao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;
    protected static ?string $recordTitleAttribute = 'data';

    public static function form(Schema $schema): Schema
    {
        return SessaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessaosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PautasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessaos::route('/'),
            'create' => CreateSessao::route('/create'),
            'edit' => EditSessao::route('/{record}/edit'),
        ];
    }
}
