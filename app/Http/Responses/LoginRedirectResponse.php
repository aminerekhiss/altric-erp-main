<?php

namespace App\Http\Responses;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportRedirects\Redirector;
use Wallo\FilamentCompanies\FilamentCompanies;

class LoginRedirectResponse extends LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Auth::user();
        $tenant = $user?->currentCompany ?? $user?->primaryCompany();

        if ($tenant && Route::has('filament.company.pages.home')) {
            return redirect()->to(route('filament.company.pages.home', ['tenant' => $tenant]));
        }

        try {
            $defaultPanelUrl = rtrim(Filament::getDefaultPanel()->getUrl(tenant: $tenant), '/') . '/home';
        } catch (NoDefaultPanelSetException) {
            $defaultPanelUrl = rtrim(Filament::getPanel(FilamentCompanies::getCompanyPanel())->getUrl(tenant: $tenant), '/') . '/home';
        }

        return redirect()->to($defaultPanelUrl);
    }
}
