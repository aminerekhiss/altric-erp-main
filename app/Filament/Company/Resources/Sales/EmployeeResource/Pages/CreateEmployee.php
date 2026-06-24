<?php

namespace App\Filament\Company\Resources\Sales\EmployeeResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeResource;
use App\Models\Common\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateEmployee extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeResource::class;

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

        /** @var Employee $employee */
        $employee = parent::handleRecordCreation($data);

        if ($loginEmail && $password) {
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $loginEmail,
                'password' => Hash::make($password),
            ]);

            if (auth()->user()?->currentCompany) {
                $user->companies()->attach(auth()->user()->currentCompany, ['role' => 'employee']);
                $user->switchCompany(auth()->user()->currentCompany);
            }

            $employee->update([
                'user_id' => $user->id,
                'email' => $employee->email ?: $loginEmail,
            ]);
        }

        return $employee;
    }
}
