<?php

namespace App\Filament\Company\Resources\Sales\AccountMessageResource\Pages;

use App\Filament\Company\Resources\Sales\AccountMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListAccountMessages extends ListRecords
{
    protected static string $resource = AccountMessageResource::class;

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
