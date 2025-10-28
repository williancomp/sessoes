<?php

namespace App\Filament\Resources\Legislaturas;

use App\Filament\Resources\Legislaturas\Pages\CreateLegislatura;
use App\Filament\Resources\Legislaturas\Pages\EditLegislatura;
use App\Filament\Resources\Legislaturas\Pages\ListLegislaturas;
use App\Filament\Resources\Legislaturas\Schemas\LegislaturaForm;
use App\Filament\Resources\Legislaturas\Tables\LegislaturasTable;
use App\Models\Legislatura;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LegislaturaResource extends Resource
{
    protected static ?string $model = Legislatura::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'ano_inicio';

    public static function form(Schema $schema): Schema
    {
        return LegislaturaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegislaturasTable::configure($table);
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
            'index' => ListLegislaturas::route('/'),
            'create' => CreateLegislatura::route('/create'),
            'edit' => EditLegislatura::route('/{record}/edit'),
        ];
    }
}
