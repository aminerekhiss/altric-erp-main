<?php

namespace App\Filament\Company\Resources\Sales\EmployeeAbsenceResource\Pages;

use App\Filament\Company\Resources\Sales\EmployeeAbsenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListEmployeeAbsences extends ListRecords
{
    protected static string $resource = EmployeeAbsenceResource::class;

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
