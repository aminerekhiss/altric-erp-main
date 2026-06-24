<x-filament-panels::page.simple>
    <div class="es-login-shell">
        <div class="es-login-bg-grid" aria-hidden="true"></div>
        <div class="es-login-bg-noise" aria-hidden="true"></div>
        <div class="es-login-glow es-login-glow-a" aria-hidden="true"></div>
        <div class="es-login-glow es-login-glow-b" aria-hidden="true"></div>
        <div class="es-login-orb es-login-orb-a"></div>
        <div class="es-login-orb es-login-orb-b"></div>

        <div class="es-login-card">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

            <x-filament-panels::form wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
        </div>
    </div>
</x-filament-panels::page.simple>
