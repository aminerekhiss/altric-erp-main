<?php

namespace App\Filament\Company\Resources\Inventory\InvoiceArchiveResource\Pages;

use App\Filament\Company\Resources\Inventory\InvoiceArchiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListInvoiceArchives extends ListRecords
{
    protected static string $resource = InvoiceArchiveResource::class;

    public function getTitle(): string
    {
        return translate('Invoice Archives');
    }

    public function getSubheading(): ?string
    {
        return translate('Search, review, and maintain all uploaded invoice files in one place.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(translate('Upload invoice'))
                ->icon('heroicon-m-arrow-up-tray'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }
}
