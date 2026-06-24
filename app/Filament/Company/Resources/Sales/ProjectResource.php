<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ProjectResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\Project;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Project Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options(Project::getStatusOptions())
                            ->default(Project::STATUS_PLANNED)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('priority')
                            ->options(Project::getPriorityOptions())
                            ->default('medium')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('due_date')
                            ->minDate(fn (Forms\Get $get) => $get('start_date')),
                        Forms\Components\Select::make('employees')
                            ->relationship(
                                'employees',
                                'full_name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Assigned employees receive a notification when assigned.'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employees.full_name')
                    ->label('Assigned Employees')
                    ->listWithLineBreaks()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Project::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Project::getPriorityOptions()),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_PROJECTS, $user, $company)) {
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
