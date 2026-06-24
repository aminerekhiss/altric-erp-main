<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\EmployeeAbsenceResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\EmployeeAbsence;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeAbsenceResource extends Resource
{
    protected static ?string $model = EmployeeAbsence::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'Absence / Conge';

    protected static ?string $pluralModelLabel = 'Absences / Conges';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Absence Details')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employee')
                            ->relationship(
                                'employee',
                                'full_name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->options(EmployeeAbsence::getTypeOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(EmployeeAbsence::getStatusOptions())
                            ->default('pending')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->minDate(fn (Forms\Get $get) => $get('start_date'))
                            ->live(),
                        Forms\Components\TextInput::make('days')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Auto-calculated from start and end date, but you can edit it.'),
                        Forms\Components\TextInput::make('reason')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => EmployeeAbsence::getTypeOptions()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(EmployeeAbsence::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(EmployeeAbsence::getStatusOptions()),
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
            'index' => Pages\ListEmployeeAbsences::route('/'),
            'create' => Pages\CreateEmployeeAbsence::route('/create'),
            'edit' => Pages\EditEmployeeAbsence::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_ABSENCES, $user, $company)) {
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
