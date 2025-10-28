<?php

namespace App\Filament\Resources\Legislaturas\Pages;

use App\Filament\Resources\Legislaturas\LegislaturaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegislaturas extends ListRecords
{
    protected static string $resource = LegislaturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
