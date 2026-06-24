<?php

namespace App\Filament\Company\Resources\Sales\InvoiceStegResource\Pages;

use App\Filament\Company\Resources\Sales\InvoiceStegResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceStegs extends ListRecords
{
    protected static string $resource = InvoiceStegResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
