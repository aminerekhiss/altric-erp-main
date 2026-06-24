<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\UserResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Company;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        Forms\Components\Select::make('company_role')
                            ->label(translate('Role'))
                            ->required()
                            ->options(static::roleOptions())
                            ->hidden(fn (?User $record): bool => static::isOwner($record)),
                        Forms\Components\TextInput::make('password')
                            ->label(translate('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label(translate('Confirm password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->same('password')
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_role')
                    ->label(translate('Role'))
                    ->state(function (User $record): string {
                        if (static::isOwner($record)) {
                            return translate('Owner');
                        }

                        return ucfirst((string) static::roleFor($record));
                    })
                    ->badge()
                    ->sortable(false),
                Tables\Columns\IconColumn::make('is_owner')
                    ->label(translate('Owner'))
                    ->boolean()
                    ->state(fn (User $record): bool => static::isOwner($record)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('remove_from_company')
                    ->label(translate('Remove'))
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! static::isOwner($record))
                    ->action(function (User $record): void {
                        $companyId = static::currentCompany()?->id;

                        if ($companyId) {
                            $record->companies()->detach($companyId);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('remove_from_company')
                        ->label(translate('Remove selected'))
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $companyId = static::currentCompany()?->id;

                            if (! $companyId) {
                                return;
                            }

                            foreach ($records as $record) {
                                if (! static::isOwner($record)) {
                                    $record->companies()->detach($companyId);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $company = static::currentCompany();

        if (! $company) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($company): void {
                $query
                    ->whereKey($company->user_id)
                    ->orWhereHas('companies', function (Builder $builder) use ($company): void {
                        $builder->where('companies.id', $company->id);
                    });
            })
            ->distinct();
    }

    protected static function canAccessFor(string $permission): bool
    {
        unset($permission);

        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if ($user->ownsCompany($company)) {
            return true;
        }

        return $user->companyRole($company)?->key === 'admin';
    }

    public static function canViewAny(): bool
    {
        return static::canAccessFor('read');
    }

    public static function canCreate(): bool
    {
        return static::canAccessFor('create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccessFor('update');
    }

    protected static function currentCompany(): ?Company
    {
        $user = auth()->user();

        return $user?->currentCompany;
    }

    public static function isOwner(?User $user): bool
    {
        $company = static::currentCompany();

        if (! $company || ! $user) {
            return false;
        }

        return (int) $company->user_id === (int) $user->id;
    }

    public static function roleFor(User $user): ?string
    {
        $company = static::currentCompany();

        if (! $company) {
            return null;
        }

        return $user->companies()
            ->where('companies.id', $company->id)
            ->first()?->membership?->role;
    }

    public static function roleOptions(): array
    {
        return [
            'admin' => 'Administrator',
            'editor' => 'Editor',
            'company' => 'Company Account',
            'employee' => 'Employee Account',
        ];
    }
}
