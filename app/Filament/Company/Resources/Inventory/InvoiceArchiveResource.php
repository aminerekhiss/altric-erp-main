<?php

namespace App\Filament\Company\Resources\Inventory;

use App\Filament\Company\Resources\Inventory\InvoiceArchiveResource\Pages;
use App\Filament\Company\Resources\Inventory\TicketResource\Pages\ViewTicket;
use App\Filament\Tables\Columns;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\Common\Ticket;
use App\Support\EmployeeModuleAccess;
use Filament\Forms\Get;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceArchiveResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'Invoice Archive';

    protected static ?string $pluralModelLabel = 'Invoice Archives';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Upload')
                    ->description(translate('Upload the invoice file and organize it into a folder for easier retrieval later.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(translate('Title'))
                            ->placeholder('e.g. March office supplies')
                            ->autofocus()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('invoice_folder')
                            ->label(translate('Folder'))
                            ->options(static::folderOptions())
                            ->default('general')
                            ->placeholder(translate('Choose a folder'))
                            ->helperText(translate('Use an existing folder or type a new folder name below.'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('new_invoice_folder')
                            ->label(translate('New folder name'))
                            ->placeholder('e.g. supplier-invoices')
                            ->dehydrated(false)
                            ->helperText(translate('Create a folder by typing a name. It will be used for this upload.'))
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                if (filled($state)) {
                                    $set('invoice_folder', static::sanitizeFolder((string) $state));
                                }
                            })
                            ->live(onBlur: true),
                        Forms\Components\FileUpload::make('invoice_file')
                            ->disk('public')
                            ->directory(fn (Get $get): string => static::invoiceDirectory($get('invoice_folder')))
                            ->required()
                            ->downloadable()
                            ->openable()
                            ->helperText(translate('Accepted: PDF, JPG, PNG, WEBP. Max size: 10MB.'))
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Invoice Details')
                    ->description(translate('Capture metadata so this invoice is searchable and report-ready.'))
                    ->schema([
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label(translate('Invoice date'))
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('invoice_from')
                            ->label(translate('From'))
                            ->placeholder('Supplier or person name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_amount')
                            ->label(translate('Amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->suffix('TND')
                            ->default(0),
                        Forms\Components\Select::make('client_id')
                            ->label(translate('Client'))
                            ->relationship(
                                'client',
                                'name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->placeholder(translate('Optional'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('product_id')
                            ->label(translate('Product'))
                            ->relationship(
                                'product',
                                'name',
                                modifyQueryUsing: static fn (Builder $query): Builder => $query->where('company_id', auth()->user()?->current_company_id)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Forms\Components\Textarea::make('invoice_description')
                            ->label(translate('Description'))
                            ->placeholder(translate('Add context for this invoice (items, purpose, notes).'))
                            ->rows(4)
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('invoice_file'))
            ->defaultSort('invoice_date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->label(translate('Ticket'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_folder')
                    ->label(translate('Folder'))
                    ->badge()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label(translate('Client'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(translate('Product'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_from')
                    ->label(translate('From whom'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_amount')
                    ->label(translate('Amount'))
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' TND')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_description')
                    ->label(translate('Description'))
                    ->limit(40)
                    ->tooltip(fn (Ticket $record): ?string => $record->invoice_description)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label(translate('Invoice date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('invoice_file')
                    ->label(translate('File'))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->state(fn (Ticket $record) => filled($record->invoice_file)),
                Tables\Columns\TextColumn::make('date')
                    ->label(translate('Ticket date'))
                    ->date()
                    ->toggleable(),
            ])
            ->filters([
                DateRangeFilter::make('invoice_date')
                    ->fromLabel(translate('Invoice from date'))
                    ->untilLabel(translate('Invoice to date'))
                    ->indicatorLabel(translate('Invoice date')),
                Tables\Filters\Filter::make('invoice_from')
                    ->label(translate('From whom'))
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label(translate('From whom'))
                            ->placeholder(translate('Type supplier/person')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = trim((string) ($data['value'] ?? ''));

                        if ($value === '') {
                            return $query;
                        }

                        return $query->where('invoice_from', 'like', "%{$value}%");
                    }),
            ])
            ->recordUrl(fn (Ticket $record): ?string => static::canEdit($record) ? static::getUrl('edit', ['record' => $record]) : null)
            ->actions([
                Tables\Actions\Action::make('openFile')
                    ->label(translate('Open invoice'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->visible(fn () => static::canViewAny())
                    ->tooltip(translate('Open the uploaded invoice in a new tab'))
                    ->url(fn (Ticket $record) => $record->invoice_file ? asset('storage/' . ltrim($record->invoice_file, '/')) : null)
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => static::canEditAny()),
                Tables\Actions\Action::make('viewTicket')
                    ->label(translate('View ticket'))
                    ->icon('heroicon-m-eye')
                    ->visible(fn () => static::canViewAny())
                    ->url(fn (Ticket $record) => ViewTicket::getUrl(['record' => $record])),
            ])
                    ->emptyStateIcon('heroicon-o-document-magnifying-glass')
                    ->emptyStateHeading(translate('No invoice archives yet'))
                    ->emptyStateDescription(translate('Upload your first invoice to start building a searchable archive.'))
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_INVOICE_ARCHIVES, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'read');
    }

    public static function canView(Model $record): bool
    {
        if (! $record instanceof Ticket || ! static::canViewAny()) {
            return false;
        }

        return (int) $record->company_id === (int) auth()->user()?->current_company_id;
    }

    public static function canCreate(): bool
    {
        return static::canAccessFor('create');
    }

    public static function canEditAny(): bool
    {
        return static::canAccessFor('update');
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof Ticket || ! static::canEditAny()) {
            return false;
        }

        return (int) $record->company_id === (int) auth()->user()?->current_company_id;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceArchives::route('/'),
            'create' => Pages\CreateInvoiceArchive::route('/create'),
            'edit' => Pages\EditInvoiceArchive::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if (! EmployeeModuleAccess::allows(EmployeeModuleAccess::MODULE_INVOICE_ARCHIVES, $user, $company)) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, $permission);
    }

    protected static function folderOptions(): array
    {
        return collect(Storage::disk('public')->directories('invoice-archives'))
            ->mapWithKeys(function (string $path): array {
                $name = Str::after($path, 'invoice-archives/');

                return [$name => $name];
            })
            ->all();
    }

    protected static function sanitizeFolder(?string $folder): string
    {
        if (blank($folder)) {
            return 'general';
        }

        return trim(Str::slug($folder, '-')) ?: 'general';
    }

    protected static function invoiceDirectory(?string $folder): string
    {
        return 'invoice-archives/' . static::sanitizeFolder($folder);
    }
}
