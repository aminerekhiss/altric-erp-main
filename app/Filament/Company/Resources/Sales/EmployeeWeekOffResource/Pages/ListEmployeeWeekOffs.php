<?php

namespace App\Filament\Company\Resources\Sales\EmployeeWeekOffResource\Pages;

use App\Filament\Company\Resources\Sales\EmployeeWeekOffResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListEmployeeWeekOffs extends ListRecords
{
    protected static string $resource = EmployeeWeekOffResource::class;

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
