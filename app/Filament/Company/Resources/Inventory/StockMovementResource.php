<?php

namespace App\Filament\Company\Resources\Inventory;

use App\Filament\Company\Resources\Inventory\StockMovementResource\Pages;
use App\Filament\Tables\Columns;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\Common\StockMovement;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $modelLabel = 'Stock Movement';

    protected static ?string $pluralModelLabel = 'Stock Movements';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(translate('Date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket.name')
                    ->label(translate('Ticket'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(translate('Product'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('direction')
                    ->badge()
                    ->color(fn (string $state) => $state === 'in' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('operation')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('before_quantity')
                    ->label(translate('Before'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('after_quantity')
                    ->label(translate('After'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->options(StockMovement::getDirectionOptions()),
                Tables\Filters\SelectFilter::make('operation')
                    ->options(function () {
                        return StockMovement::query()
                            ->select('operation')
                            ->distinct()
                            ->pluck('operation', 'operation')
                            ->toArray();
                    }),
                DateRangeFilter::make('created_at')
                    ->fromLabel(translate('From date'))
                    ->untilLabel(translate('To date'))
                    ->indicatorLabel(translate('Movement date')),
                Tables\Filters\Filter::make('ticket_name')
                    ->label(translate('Ticket'))
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label(translate('Ticket name'))
                            ->placeholder(translate('Type ticket name')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return $query;
                        }

                        return $query->whereHas('ticket', function ($subQuery) use ($value) {
                            $subQuery->where('name', 'like', "%{$value}%");
                        });
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Company\Resources\Inventory\StockMovementResource\Pages\ListStockMovements::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_STOCK_MOVEMENTS, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'read');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
