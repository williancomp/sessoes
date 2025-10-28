<?php

namespace App\Filament\Resources\Pautas\Pages;

use App\Filament\Resources\Pautas\PautaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPauta extends EditRecord
{
    protected static string $resource = PautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
