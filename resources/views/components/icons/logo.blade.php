@php
    $isCompanyLogin = request()->is('company/login');

    $tenant = auth()->user()?->currentCompany ?? auth()->user()?->primaryCompany();

    $homeUrl = '#';

    if ($tenant && \Illuminate\Support\Facades\Route::has('filament.company.pages.home')) {
        $homeUrl = route('filament.company.pages.home', ['tenant' => $tenant]);
    } elseif (\Illuminate\Support\Facades\Route::has('filament.user.home')) {
        $homeUrl = route('filament.user.home');
    }
@endphp

@if ($isCompanyLogin)
    <h1 class="es-login-brand-word es-login-page-logo">ALTRIC</h1>
@else
    <a href="{{ $homeUrl }}" class="inline-flex h-full items-center" aria-label="Go to home launcher">
        <svg
            viewBox="0 0 370.25 66.915"
            fill="currentColor"
            class="h-5/6 text-gray-700 dark:text-gray-200"
        >
            <text
                x="50%"
                y="51"
                text-anchor="middle"
                font-size="52"
                font-weight="700"
                letter-spacing="2"
            >ALTRIC</text>
        </svg>
    </a>
@endif
