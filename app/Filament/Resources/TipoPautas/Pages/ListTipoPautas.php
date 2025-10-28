<?php

namespace App\Filament\Resources\TipoPautas\Pages;

use App\Filament\Resources\TipoPautas\TipoPautaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoPautas extends ListRecords
{
    protected static string $resource = TipoPautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
