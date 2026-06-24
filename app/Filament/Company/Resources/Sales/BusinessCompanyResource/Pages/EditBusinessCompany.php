<?php

namespace App\Filament\Company\Resources\Sales\BusinessCompanyResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\BusinessCompanyResource;
use App\Models\Common\BusinessCompany;
use App\Models\Common\Employee;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditBusinessCompany extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = BusinessCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var BusinessCompany $record */
        $record = $this->record;

        $data['login_email'] = $record->user?->email;

        return $data;
    }

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BusinessCompany $record */
        $businessCompany = $record;

        $loginEmail = $data['login_email'] ?? null;
        $password = $data['password'] ?? null;

        if ($loginEmail) {
            validator([
                'login_email' => $loginEmail,
            ], [
                'login_email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($businessCompany->user_id),
                ],
            ], [
                'login_email.unique' => 'This login email is already used by another account.',
            ])->validate();
        }

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        /** @var BusinessCompany $businessCompany */
        $businessCompany = parent::handleRecordUpdate($record, $data);

        if ($loginEmail) {
            $user = $businessCompany->user;

            if (! $user) {
                if (! $password) {
                    throw ValidationException::withMessages([
                        'data.password' => 'Password is required when creating a new login account.',
                    ]);
                }

                $user = User::create([
                    'name' => $businessCompany->name,
                    'email' => $loginEmail,
                    'password' => Hash::make($password),
                ]);

                if (auth()->user()?->currentCompany) {
                    $user->companies()->attach(auth()->user()->currentCompany, ['role' => 'company']);
                    $user->switchCompany(auth()->user()->currentCompany);
                }

                $businessCompany->user_id = $user->id;
            } else {
                $user->name = $businessCompany->name;
                $user->email = $loginEmail;
            }

            if ($password) {
                $user->password = Hash::make($password);
            }

            $user->save();

            $businessCompany->email_primary = $businessCompany->email_primary ?: $loginEmail;
            $businessCompany->save();
        }

        return $businessCompany;
    }
}
