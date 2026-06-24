<?php

namespace App\Filament\Company\Resources\Sales\EmployeeResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\EmployeeResource;
use App\Models\Common\Employee;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditEmployee extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Employee $record */
        $record = $this->record;

        $data['login_email'] = $record->user?->email;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Employee $record */
        $employee = $record;

        $loginEmail = $data['login_email'] ?? null;
        $password = $data['password'] ?? null;

        if ($loginEmail) {
            validator([
                'login_email' => $loginEmail,
            ], [
                'login_email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($employee->user_id),
                ],
            ], [
                'login_email.unique' => 'This login email is already used by another account.',
            ])->validate();
        }

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        /** @var Employee $employee */
        $employee = parent::handleRecordUpdate($record, $data);

        if ($loginEmail) {
            $user = $employee->user;

            if (! $user) {
                if (! $password) {
                    throw ValidationException::withMessages([
                        'data.password' => 'Password is required when creating a new login account.',
                    ]);
                }

                $user = User::create([
                    'name' => $employee->full_name,
                    'email' => $loginEmail,
                    'password' => Hash::make($password),
                ]);

                if (auth()->user()?->currentCompany) {
                    $user->companies()->attach(auth()->user()->currentCompany, ['role' => 'employee']);
                    $user->switchCompany(auth()->user()->currentCompany);
                }

                $employee->user_id = $user->id;
            } else {
                $user->name = $employee->full_name;
                $user->email = $loginEmail;
            }

            if ($password) {
                $user->password = Hash::make($password);
            }

            $user->save();

            $employee->email = $employee->email ?: $loginEmail;
            $employee->save();
        }

        return $employee;
    }
}
