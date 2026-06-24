<?php

namespace App\Filament\Company\Resources\Inventory;

use App\Filament\Company\Resources\Inventory\TicketResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\Ticket;
use App\Services\TicketStockService;
use App\Support\EmployeeModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets';

    protected static function canManageTicketActions(?Ticket $record = null): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if ($record && (int) $record->company_id !== (int) $company->id) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_TICKETS, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'update');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(Ticket::getTypeOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(Ticket::getStatusOptions())
                            ->default(Ticket::STATUS_DRAFT)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('provider')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(company_today()->toDateString()),
                        Forms\Components\Select::make('product_mode')
                            ->label(translate('Product entry mode'))
                            ->options([
                                'select' => translate('Select existing product'),
                                'new' => translate('Write a new product'),
                            ])
                            ->default('select')
                            ->live()
                            ->dehydrated(false)
                            ->native(false),
                        Forms\Components\Select::make('product_id')
                            ->label(translate('Product'))
                            ->relationship(
                                'product',
                                'name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->required(fn (Forms\Get $get) => ($get('product_mode') ?? 'select') === 'select')
                            ->hidden(fn (Forms\Get $get) => ($get('product_mode') ?? 'select') === 'new')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('new_product_name')
                            ->label(translate('New product name'))
                            ->required(fn (Forms\Get $get) => ($get('product_mode') ?? 'select') === 'new')
                            ->hidden(fn (Forms\Get $get) => ($get('product_mode') ?? 'select') !== 'new')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\FileUpload::make('logo')
                            ->image()
                            ->directory('logos/tickets')
                            ->maxSize(1024),
                        Forms\Components\FileUpload::make('invoice_file')
                            ->label(translate('Invoice archive file'))
                            ->directory('tickets/invoices')
                            ->openable()
                            ->downloadable()
                            ->maxSize(8192)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/png',
                                'image/jpeg',
                                'image/webp',
                            ]),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label(translate('Invoice date')),
                        Forms\Components\TextInput::make('invoice_from')
                            ->label(translate('Invoice from (supplier/person)'))
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
            ->defaultSort('date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Ticket::getTypeOptions()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(translate('Product'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('invoice_from')
                    ->label(translate('Invoice from'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(translate('Invoice date'))
                    ->date()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('invoice_file')
                    ->label(translate('Invoice file'))
                    ->boolean()
                    ->state(fn (Ticket $record) => filled($record->invoice_file))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Ticket::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Ticket::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label(translate('Confirm'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Ticket $record) => ! $record->isValidated() && static::canManageTicketActions($record))
                    ->action(function (Ticket $record) {
                        abort_unless(static::canManageTicketActions($record), 403, 'Unauthorized action.');

                        DB::transaction(function () use ($record) {
                            if ($record->isValidated()) {
                                return;
                            }

                            $record->update([
                                'status' => Ticket::STATUS_VALIDATED,
                            ]);

                            app(TicketStockService::class)->apply($record, 'confirm');
                        });
                    })
                    ->successNotificationTitle(translate('Ticket confirmed successfully')),
                Tables\Actions\Action::make('cancel')
                    ->label(translate('Cancel'))
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Ticket $record) => $record->isValidated() && static::canManageTicketActions($record))
                    ->action(function (Ticket $record) {
                        abort_unless(static::canManageTicketActions($record), 403, 'Unauthorized action.');

                        DB::transaction(function () use ($record) {
                            if (! $record->isValidated()) {
                                return;
                            }

                            app(TicketStockService::class)->revert($record, 'cancel');

                            $record->update([
                                'status' => Ticket::STATUS_CANCELLED,
                            ]);
                        });
                    })
                    ->successNotificationTitle(translate('Ticket cancelled and stock reversed')),
                Tables\Actions\Action::make('reopen')
                    ->label(translate('Reopen'))
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Ticket $record) => $record->status === Ticket::STATUS_CANCELLED && static::canManageTicketActions($record))
                    ->action(function (Ticket $record) {
                        abort_unless(static::canManageTicketActions($record), 403, 'Unauthorized action.');

                        $record->update([
                            'status' => Ticket::STATUS_DRAFT,
                        ]);
                    })
                    ->successNotificationTitle(translate('Ticket reopened as draft')),
                Tables\Actions\Action::make('print')
                    ->label(translate('Print'))
                    ->icon('heroicon-m-printer')
                    ->url(fn (Ticket $record) => route('tickets.print', ['ticket' => $record]))
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

    public static function canCreate(): bool
    {
        return static::canManageTicketActions();
    }

    public static function canViewAny(): bool
    {
        return static::canManageTicketActions();
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof Ticket) {
            return false;
        }

        return static::canManageTicketActions($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof Ticket) {
            return false;
        }

        return static::canManageTicketActions($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
