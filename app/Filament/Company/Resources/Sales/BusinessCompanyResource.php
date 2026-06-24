<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\BusinessCompanyResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\BusinessCompany;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BusinessCompanyResource extends Resource
{
    protected static ?string $model = BusinessCompany::class;

    protected static ?string $modelLabel = 'Company';

    protected static ?string $pluralModelLabel = 'Companies';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->description(translate('Core identity and branding for the company.'))
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('logos/business-companies')
                            ->helperText(translate('Recommended: square logo, up to 1MB.'))
                            ->maxSize(1024),
                        Forms\Components\TextInput::make('name')
                            ->placeholder('Ex: Altric Services')
                            ->autofocus()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->placeholder('https://example.com')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Contact')
                    ->description(translate('Main communication channels for this company.'))
                    ->schema([
                        Forms\Components\TextInput::make('email_primary')
                            ->label(translate('Primary email'))
                            ->placeholder('contact@company.com')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email_secondary')
                            ->label(translate('Secondary email'))
                            ->placeholder('support@company.com')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_primary')
                            ->label(translate('Primary phone'))
                            ->placeholder('+216 00 000 000')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_secondary')
                            ->label(translate('Secondary phone'))
                            ->placeholder('+216 00 000 001')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_tertiary')
                            ->label(translate('Third phone'))
                            ->placeholder('+216 00 000 002')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Employees')
                    ->description(translate('Link employees who are part of this company entity.'))
                    ->schema([
                        Forms\Components\Select::make('employees')
                            ->relationship(
                                'employees',
                                'full_name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(translate('Useful for mission assignment and communication.')),
                    ]),
                Forms\Components\Section::make('Login Access')
                    ->description(translate('Create or update the company login account.'))
                    ->schema([
                        Forms\Components\Placeholder::make('account_role')
                            ->label(translate('Account role'))
                            ->content(translate('Company account')),
                        Forms\Components\Placeholder::make('account_status')
                            ->label(translate('Account status'))
                            ->content(fn (?BusinessCompany $record) => $record?->user_id ? translate('Login account created') : translate('No login account yet')),
                        Forms\Components\TextInput::make('login_email')
                            ->label(translate('Login email'))
                            ->placeholder('login@company.com')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label(translate('Password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->helperText(translate('Min 8 characters. Leave empty to keep current password.'))
                            ->minLength(8),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label(translate('Confirm password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->same('password'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Columns::id(),
                Tables\Columns\ImageColumn::make('logo')
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder-logo.svg')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_primary')
                    ->label(translate('Email'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('website')
                    ->copyable()
                    ->url(fn (BusinessCompany $record): ?string => $record->website)
                    ->openUrlInNewTab()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone_primary')
                    ->label(translate('Phone'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label(translate('Employees'))
                    ->counts('employees')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label(translate('Login email'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label(translate('Has account'))
                    ->boolean()
                    ->state(fn (BusinessCompany $record) => filled($record->user_id))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_account')
                    ->label(translate('Has login account'))
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('user_id'),
                        false: fn ($query) => $query->whereNull('user_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBusinessCompanies::route('/'),
            'create' => Pages\CreateBusinessCompany::route('/create'),
            'edit' => Pages\EditBusinessCompany::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_BUSINESS_COMPANIES, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, $permission);
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

    public static function canDelete(Model $record): bool
    {
        return static::canAccessFor('delete');
    }
}
