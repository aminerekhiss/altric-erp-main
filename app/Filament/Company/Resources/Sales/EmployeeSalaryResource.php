<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Exports\Common\EmployeeSalaryExporter;
use App\Filament\Company\Resources\Sales\EmployeeSalaryResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\EmployeeSalary;
use App\Services\EmployeeSalaryService;
use App\Support\EmployeeModuleAccess;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryResource extends Resource
{
    protected static ?string $model = EmployeeSalary::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $modelLabel = 'Salary';

    protected static ?string $pluralModelLabel = 'Salaries';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Salary Details')
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
                        Forms\Components\DatePicker::make('salary_month')
                            ->label('Salary month')
                            ->required()
                            ->default(company_today()->startOfMonth()->toDateString())
                            ->helperText('Any date in the month works. It is stored as the first day of that month.'),
                        Forms\Components\TextInput::make('base_salary')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('bonus')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('deduction')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\Select::make('status')
                            ->options(EmployeeSalary::getStatusOptions())
                            ->default(EmployeeSalary::STATUS_DRAFT)
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->seconds(false)
                            ->hidden(fn (Forms\Get $get) => $get('status') !== EmployeeSalary::STATUS_PAID),
                        Forms\Components\TextInput::make('paid_days')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('absent_days')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('week_off_days')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('net_salary')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('salary_month', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salary_month')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_salary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deduction')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_salary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_days')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('absent_days')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('week_off_days')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EmployeeSalary::getStatusOptions()),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->exporter(EmployeeSalaryExporter::class),
            ])
            ->actions([
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-m-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (EmployeeSalary $record): void {
                        $result = app(EmployeeSalaryService::class)->calculate(
                            employee: $record->employee,
                            salaryMonth: Carbon::parse($record->salary_month)->toDateString(),
                            baseSalary: (int) $record->base_salary,
                            bonus: (int) $record->bonus,
                            deduction: (int) $record->deduction,
                        );

                        $record->update($result);
                    }),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->url(fn (EmployeeSalary $record) => route('employee-salaries.print', ['employeeSalary' => $record]))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListEmployeeSalaries::route('/'),
            'create' => Pages\CreateEmployeeSalary::route('/create'),
            'edit' => Pages\EditEmployeeSalary::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_SALARIES, $user, $company)) {
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
