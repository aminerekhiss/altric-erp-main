<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\EmployeeAttendanceResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\EmployeeAttendance;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceResource extends Resource
{
    protected static ?string $model = EmployeeAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Attendance';

    protected static ?string $pluralModelLabel = 'Attendance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Details')
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
                        Forms\Components\DatePicker::make('attendance_date')
                            ->required()
                            ->default(company_today()->toDateString()),
                        Forms\Components\Select::make('status')
                            ->options(EmployeeAttendance::getStatusOptions())
                            ->required()
                            ->default(EmployeeAttendance::STATUS_PRESENT)
                            ->native(false),
                        Forms\Components\DateTimePicker::make('check_in')
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('check_out')
                            ->seconds(false),
                        Forms\Components\TextInput::make('worked_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Auto-calculated from check in/out when both are filled.'),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('attendance_date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => EmployeeAttendance::getStatusOptions()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('check_in')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('worked_minutes')
                    ->label('Worked (min)')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(EmployeeAttendance::getStatusOptions()),
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
            'index' => Pages\ListEmployeeAttendances::route('/'),
            'create' => Pages\CreateEmployeeAttendance::route('/create'),
            'edit' => Pages\EditEmployeeAttendance::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_EMPLOYEE_ATTENDANCES, $user, $company)) {
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
