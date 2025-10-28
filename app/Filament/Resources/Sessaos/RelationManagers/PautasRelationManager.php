<?php

namespace App\Filament\Resources\Sessaos\RelationManagers;

use App\Filament\Resources\Pautas\Schemas\PautaForm;
use App\Filament\Resources\Pautas\Tables\PautasTable;
use App\Filament\Resources\Pautas\Tables\PautaTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PautasRelationManager extends RelationManager
{
    protected static string $relationship = 'pautas';

    public function form(Schema $schema): Schema
    {
        return PautaForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return PautasTable::configure($table);
    }
}
