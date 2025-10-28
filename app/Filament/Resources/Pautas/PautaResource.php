<?php

namespace App\Filament\Resources\Pautas;

use App\Filament\Resources\Pautas\Pages\CreatePauta;
use App\Filament\Resources\Pautas\Pages\EditPauta;
use App\Filament\Resources\Pautas\Pages\ListPautas;
use App\Filament\Resources\Pautas\Schemas\PautaForm;
use App\Filament\Resources\Pautas\Tables\PautasTable;
use App\Models\Pauta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PautaResource extends Resource
{
    protected static ?string $model = Pauta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return PautaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PautasTable::configure($table);
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
            'index' => ListPautas::route('/'),
            'create' => CreatePauta::route('/create'),
            'edit' => EditPauta::route('/{record}/edit'),
        ];
    }
}
