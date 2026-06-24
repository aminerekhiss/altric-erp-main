<?php

namespace App\Filament\Pages\Auth;

use Illuminate\Contracts\Support\Htmlable;
use Wallo\FilamentCompanies\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        // Hide the default "Sign in" heading for the company login page.
        return '';
    }

    public function mount(): void
    {
        parent::mount();

        if (is_demo_environment()) {
            $this->form->fill([
                'email' => 'admin@altric.com',
                'password' => 'password',
                'remember' => true,
            ]);
        }
    }
}
