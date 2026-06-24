<?php

namespace App\Filament\Company\Resources\Sales\UserResource\Pages;

use App\Filament\Company\Resources\Sales\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;

        $data['company_role'] = UserResource::roleFor($user);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $user */
        $user = $record;

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $company = auth()->user()?->currentCompany;

        if (
            $company
            && ! UserResource::isOwner($user)
            && ! empty($data['company_role'])
        ) {
            $user->companies()->updateExistingPivot($company->id, [
                'role' => $data['company_role'],
            ]);
        }

        return $user;
    }
}
