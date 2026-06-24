<?php

namespace App\Filament\Company\Resources\Sales\ProjectResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\ProjectResource;
use App\Models\Common\Employee;
use App\Models\Common\Project;
use App\Notifications\ProjectAssignedNotification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateProject extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Project $project */
        $project = parent::handleRecordCreation($data);

        $project->loadMissing(['employees.user']);

        foreach ($project->employees as $employee) {
            if ($employee->user) {
                $employee->user->notify(new ProjectAssignedNotification($project));
            }
        }

        return $project;
    }
}
