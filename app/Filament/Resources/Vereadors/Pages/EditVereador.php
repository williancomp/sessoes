<?php

namespace App\Filament\Resources\Vereadors\Pages;

use App\Filament\Resources\Vereadors\VereadorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVereador extends EditRecord
{
    protected static string $resource = VereadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
