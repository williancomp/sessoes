<?php

namespace App\Filament\Resources\Vereadors\Pages;

use App\Filament\Resources\Vereadors\VereadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVereadors extends ListRecords
{
    protected static string $resource = VereadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
