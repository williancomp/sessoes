<?php

namespace App\Filament\Resources\TipoPautas\Pages;

use App\Filament\Resources\TipoPautas\TipoPautaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoPauta extends EditRecord
{
    protected static string $resource = TipoPautaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
