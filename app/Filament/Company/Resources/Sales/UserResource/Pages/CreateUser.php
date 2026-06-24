<?php

namespace App\Filament\Company\Resources\Sales\UserResource\Pages;

use App\Filament\Company\Resources\Sales\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $company = auth()->user()?->currentCompany;

        if (! $company) {
            throw ValidationException::withMessages([
                'data.company_role' => 'No active company selected.',
            ]);
        }

        $role = $data['company_role'] ?? null;

        if (! $role) {
            throw ValidationException::withMessages([
                'data.company_role' => 'Role is required.',
            ]);
        }

        $password = $data['password'] ?? null;

        if (! filled($password)) {
            throw ValidationException::withMessages([
                'data.password' => 'Password is required.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
        ]);

        $user->companies()->attach($company, ['role' => $role]);
        $user->switchCompany($company);

        return $user;
    }
}
