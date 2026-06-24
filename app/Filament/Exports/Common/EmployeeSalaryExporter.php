<?php

namespace App\Filament\Exports\Common;

use App\Models\Common\EmployeeSalary;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EmployeeSalaryExporter extends Exporter
{
    protected static ?string $model = EmployeeSalary::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.full_name')
                ->label('Employee'),
            ExportColumn::make('salary_month')
                ->date(),
            ExportColumn::make('status')
                ->enum(),
            ExportColumn::make('base_salary'),
            ExportColumn::make('bonus'),
            ExportColumn::make('deduction'),
            ExportColumn::make('net_salary'),
            ExportColumn::make('paid_days'),
            ExportColumn::make('absent_days'),
            ExportColumn::make('week_off_days'),
            ExportColumn::make('paid_at')
                ->dateTime(),
            ExportColumn::make('created_at')
                ->dateTime()
                ->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your salary export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
