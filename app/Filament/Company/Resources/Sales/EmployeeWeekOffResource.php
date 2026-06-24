<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\EmployeeWeekOffResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\EmployeeWeekOff;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeWeekOffResource extends Resource
{
    protected static ?string $model = EmployeeWeekOff::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $modelLabel = 'Week Off';

    protected static ?string $pluralModelLabel = 'Weeks Off';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Week Off Details')
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
                        Forms\Components\Select::make('weekday')
                            ->options(EmployeeWeekOff::getWeekdayOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Paid week off')
                            ->default(true),
                        Forms\Components\DatePicker::make('effective_from'),
                        Forms\Components\DatePicker::make('effective_to')
                            ->minDate(fn (Forms\Get $get) => $get('effective_from')),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('employee_id')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weekday')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => EmployeeWeekOff::getWeekdayOptions()[$state] ?? (string) $state)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('effective_from')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('effective_to')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Paid week off'),
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
            'index' => Pages\ListEmployeeWeekOffs::route('/'),
            'create' => Pages\CreateEmployeeWeekOff::route('/create'),
            'edit' => Pages\EditEmployeeWeekOff::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_WEEK_OFFS, $user, $company)) {
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
