<?php

namespace App\Filament\Resources\Pautas\Pages;

use App\Filament\Resources\Pautas\PautaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPautas extends ListRecords
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
