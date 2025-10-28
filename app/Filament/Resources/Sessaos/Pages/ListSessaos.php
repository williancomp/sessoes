<?php

namespace App\Filament\Resources\Sessaos\Pages;

use App\Filament\Resources\Sessaos\SessaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessaos extends ListRecords
{
    protected static string $resource = SessaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
