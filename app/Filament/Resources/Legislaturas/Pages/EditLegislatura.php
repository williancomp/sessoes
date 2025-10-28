<?php

namespace App\Filament\Resources\Legislaturas\Pages;

use App\Filament\Resources\Legislaturas\LegislaturaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegislatura extends EditRecord
{
    protected static string $resource = LegislaturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
