<?php

namespace App\Filament\Company\Resources\Inventory\ProductResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Inventory\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = ProductResource::class;
}
