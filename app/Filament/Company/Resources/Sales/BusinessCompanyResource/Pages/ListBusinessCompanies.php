<?php

namespace App\Filament\Company\Resources\Sales\BusinessCompanyResource\Pages;

use App\Filament\Company\Resources\Sales\BusinessCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListBusinessCompanies extends ListRecords
{
    protected static string $resource = BusinessCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }
}
