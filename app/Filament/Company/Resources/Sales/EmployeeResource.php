<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\EmployeeResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\Employee;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->description('Basic profile and company assignment for this employee.')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full name')
                            ->placeholder('Ex: Mohamed Ben Ali')
                            ->autofocus()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rib')
                            ->label('RIB')
                            ->placeholder('Bank account / RIB')
                            ->helperText('Optional. Used for salary transfers.')
                            ->maxLength(255),
                        Forms\Components\Select::make('companies')
                            ->label('Assigned companies')
                            ->relationship('companies', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Assign this employee to one or more business companies.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Contact')
                    ->description('Main contact channels used by managers and HR.')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->placeholder('employee@company.com')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->placeholder('+216 00 000 000')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Login Access')
                    ->description('Create or update the employee login account.')
                    ->schema([
                        Forms\Components\Placeholder::make('account_role')
                            ->label('Account role')
                            ->content('Employee account'),
                        Forms\Components\Placeholder::make('account_status')
                            ->label('Account status')
                            ->content(fn (?Employee $record) => $record?->user_id ? 'Login account created' : 'No login account yet'),
                        Forms\Components\TextInput::make('login_email')
                            ->label('Login email')
                            ->placeholder('login@company.com')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->helperText('Min 8 characters. Leave empty to keep current password.')
                            ->minLength(8),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm password')
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
            ->defaultSort('full_name')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rib')
                    ->label('RIB')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('companies.name')
                    ->label('Companies')
                    ->listWithLineBreaks()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employee_module_access')
                    ->label('Enabled modules')
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->separator(',')
                    ->formatStateUsing(function (mixed $state): string {
                        $access = EmployeeModuleAccess::normalize(is_array($state) ? $state : null);
                        $labels = EmployeeModuleAccess::labels();

                        $enabled = [];

                        foreach ($access as $moduleKey => $isEnabled) {
                            if ($isEnabled && array_key_exists($moduleKey, $labels)) {
                                $enabled[] = $labels[$moduleKey];
                            }
                        }

                        return $enabled === [] ? 'None' : implode(',', $enabled);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('absences_count')
                    ->label('Absences / Conges')
                    ->counts('absences')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Login email')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Has account')
                    ->boolean()
                    ->state(fn (Employee $record) => filled($record->user_id))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_account')
                    ->label('Has login account')
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEES, $user, $company)) {
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

    public static function canEditAny(): bool
    {
        return static::canAccessFor('update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccessFor('delete');
    }
}
