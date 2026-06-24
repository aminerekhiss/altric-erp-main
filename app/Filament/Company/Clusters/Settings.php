<?php

namespace App\Filament\Company\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->currentCompany && $user->ownsCompany($user->currentCompany));
    }
}
