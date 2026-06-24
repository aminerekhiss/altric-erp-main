<?php

namespace App\Filament\Company\Resources\Core\DepartmentResource\Pages;

use App\Filament\Company\Resources\Core\DepartmentResource;
use App\Models\Core\Department;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $company = auth()->user()?->currentCompany;
        $companyId = $company?->id;

        if (filled($data['manager_id'] ?? null)) {
            $isManagerAllowed = (bool) $company?->allUsers()->contains('id', (int) $data['manager_id']);

            if (! $isManagerAllowed) {
                throw ValidationException::withMessages([
                    'data.manager_id' => 'Selected manager is invalid for your company.',
                ]);
            }

            $data['manager_id'] = (int) $data['manager_id'];
        } else {
            $data['manager_id'] = null;
        }

        if (filled($data['parent_id'] ?? null)) {
            $parent = Department::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->find((int) $data['parent_id']);

            if (! $parent) {
                throw ValidationException::withMessages([
                    'data.parent_id' => 'Selected parent department is invalid for your company.',
                ]);
            }

            $data['parent_id'] = (int) $parent->id;
        } else {
            $data['parent_id'] = null;
        }

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
