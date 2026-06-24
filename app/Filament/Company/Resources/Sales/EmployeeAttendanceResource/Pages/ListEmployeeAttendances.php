<?php

namespace App\Filament\Company\Resources\Sales\EmployeeAttendanceResource\Pages;

use App\Filament\Company\Resources\Sales\EmployeeAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListEmployeeAttendances extends ListRecords
{
    protected static string $resource = EmployeeAttendanceResource::class;

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
