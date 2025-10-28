<?php

namespace App\Filament\Resources\Sessaos\Pages;

use App\Filament\Resources\Sessaos\SessaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessao extends EditRecord
{
    protected static string $resource = SessaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
