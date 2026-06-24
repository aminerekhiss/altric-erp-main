<?php

namespace App\Filament\Company\Resources\Inventory\ProductResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Inventory\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
