<?php

namespace App\Filament\Company\Resources\Sales\BusinessCompanyResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\BusinessCompanyResource;
use App\Models\Common\BusinessCompany;
use App\Models\Common\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateBusinessCompany extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = BusinessCompanyResource::class;

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
        $loginEmail = $data['login_email'] ?? null;
        $password = $data['password'] ?? null;

        if ($loginEmail && ! $password) {
            throw ValidationException::withMessages([
                'data.password' => 'Password is required when login email is provided.',
            ]);
        }

        if ($loginEmail) {
            validator([
                'login_email' => $loginEmail,
            ], [
                'login_email' => ['required', 'email', Rule::unique('users', 'email')],
            ], [
                'login_email.unique' => 'This login email is already used by another account.',
            ])->validate();
        }

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        /** @var BusinessCompany $businessCompany */
        $businessCompany = parent::handleRecordCreation($data);

        if ($loginEmail && $password) {
            $user = User::create([
                'name' => $businessCompany->name,
                'email' => $loginEmail,
                'password' => Hash::make($password),
            ]);

            if (auth()->user()?->currentCompany) {
                $user->companies()->attach(auth()->user()->currentCompany, ['role' => 'company']);
                $user->switchCompany(auth()->user()->currentCompany);
            }

            $businessCompany->update([
                'user_id' => $user->id,
                'email_primary' => $businessCompany->email_primary ?: $loginEmail,
            ]);
        }

        return $businessCompany;
    }
}
