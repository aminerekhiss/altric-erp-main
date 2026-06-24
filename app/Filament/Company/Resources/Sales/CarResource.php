<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\CarResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\Car;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $modelLabel = 'Car';

    protected static ?string $pluralModelLabel = 'Cars';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Car Information')
                    ->schema([
                        Forms\Components\TextInput::make('car_number')
                            ->label('Car number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('employees')
                            ->relationship(
                                'employees',
                                'full_name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->multiple()
                            ->required()
                            ->minItems(1)
                            ->maxItems(2)
                            ->preload()
                            ->searchable()
                            ->helperText('Select one or two employees for this car.'),
                        Forms\Components\TextInput::make('mission')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('mission_date')
                            ->label('Mission date')
                            ->required()
                            ->default(company_today()->toDateString()),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Financial Tracking (Optional)')
                    ->schema([
                        Forms\Components\DatePicker::make('assurance_date')
                            ->label('Assurance date'),
                        Forms\Components\TextInput::make('assurance_amount')
                            ->label('Assurance amount')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('TND'),
                        Forms\Components\DatePicker::make('vignette_date')
                            ->label('Vignette date'),
                        Forms\Components\TextInput::make('vignette_amount')
                            ->label('Vignette amount')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('TND'),
                        Forms\Components\DatePicker::make('visite_date')
                            ->label('Visite date'),
                        Forms\Components\TextInput::make('visite_amount')
                            ->label('Visite amount')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('TND'),
                        Forms\Components\DatePicker::make('additional_cost_date')
                            ->label('Additional cost date'),
                        Forms\Components\TextInput::make('additional_cost_amount')
                            ->label('Additional cost amount')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('TND'),
                        Forms\Components\TextInput::make('additional_cost_note')
                            ->label('Additional cost note')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Additional Costs History (Optional)')
                    ->schema([
                        Forms\Components\Repeater::make('carCosts')
                            ->relationship('carCosts')
                            ->schema([
                                Forms\Components\Select::make('cost_type')
                                    ->label('Cost type')
                                    ->options([
                                        'additional' => 'Additional',
                                        'fuel' => 'Fuel',
                                        'maintenance' => 'Maintenance',
                                        'repair' => 'Repair',
                                        'other' => 'Other',
                                    ])
                                    ->default('additional')
                                    ->required(),
                                Forms\Components\DatePicker::make('cost_date')
                                    ->label('Cost date')
                                    ->default(company_today()->toDateString()),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('TND'),
                                Forms\Components\TextInput::make('note')
                                    ->label('Note')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add cost')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('mission_date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('car_number')
                    ->label('Car number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employees.full_name')
                    ->label('Employees')
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('mission')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mission_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assurance_date')
                    ->label('Assurance date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assurance_amount')
                    ->label('Assurance amount')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vignette_date')
                    ->label('Vignette date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vignette_amount')
                    ->label('Vignette amount')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('visite_date')
                    ->label('Visite date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('visite_amount')
                    ->label('Visite amount')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('additional_cost_date')
                    ->label('Additional cost date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('additional_cost_amount')
                    ->label('Additional cost amount')
                    ->numeric(decimalPlaces: 3)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('additional_cost_note')
                    ->label('Additional cost note')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
            ])
            ->filters([
                //
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
            'index' => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit' => Pages\EditCar::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_CARS, $user, $company)) {
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
