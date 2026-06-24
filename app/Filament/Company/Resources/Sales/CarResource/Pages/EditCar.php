<?php

namespace App\Filament\Company\Resources\Sales\CarResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\CarResource;
use App\Models\Common\Employee;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCar extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = CarResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $companyId = Auth::user()?->current_company_id;

        if (! $companyId) {
            return $data;
        }

        $requestedEmployeeIds = collect($data['employees'] ?? [])->map(fn ($id): int => (int) $id);

        $data['company_id'] = $companyId;
        $data['employees'] = Employee::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $requestedEmployeeIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
