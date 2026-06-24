<?php

namespace App\Filament\Company\Resources\Sales\ProjectResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\ProjectResource;
use App\Models\Common\Employee;
use App\Models\Common\Project;
use App\Notifications\ProjectAssignedNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditProject extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = ProjectResource::class;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Project $project */
        $project = $record;

        $previousEmployeeIds = $project->employees()->pluck('employees.id')->map(fn ($id): int => (int) $id)->all();

        /** @var Project $project */
        $project = parent::handleRecordUpdate($record, $data);

        $project->loadMissing(['employees.user']);
        $currentEmployeeIds = $project->employees->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $newlyAssignedEmployeeIds = array_values(array_diff($currentEmployeeIds, $previousEmployeeIds));

        foreach ($project->employees as $employee) {
            if (! in_array((int) $employee->id, $newlyAssignedEmployeeIds, true)) {
                continue;
            }

            if ($employee->user) {
                $employee->user->notify(new ProjectAssignedNotification($project));
            }
        }

        return $project;
    }
}
