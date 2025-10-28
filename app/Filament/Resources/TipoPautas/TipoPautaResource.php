<?php

namespace App\Filament\Resources\TipoPautas;

use App\Filament\Resources\TipoPautas\Pages\CreateTipoPauta;
use App\Filament\Resources\TipoPautas\Pages\EditTipoPauta;
use App\Filament\Resources\TipoPautas\Pages\ListTipoPautas;
use App\Filament\Resources\TipoPautas\Schemas\TipoPautaForm;
use App\Filament\Resources\TipoPautas\Tables\TipoPautasTable;
use App\Models\TipoPauta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TipoPautaResource extends Resource
{
    protected static ?string $model = TipoPauta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $recordTitleAttribute = 'descricao';

    

    public static function form(Schema $schema): Schema
    {
        return TipoPautaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TipoPautasTable::configure($table);
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
            'index' => ListTipoPautas::route('/'),
            'create' => CreateTipoPauta::route('/create'),
            'edit' => EditTipoPauta::route('/{record}/edit'),
        ];
    }
}
