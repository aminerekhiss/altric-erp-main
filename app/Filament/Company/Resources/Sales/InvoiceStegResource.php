<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\InvoiceStegResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\InvoiceSteg;
use App\Support\InvoiceStegCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InvoiceStegResource extends Resource
{
    protected static ?string $model = InvoiceSteg::class;

    protected static ?string $tenantRelationshipName = 'invoiceStegs';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'INVOICE STEG';

    protected static ?string $navigationParentItem = 'Invoices Parametrables';

    protected static ?string $modelLabel = 'INVOICE STEG';

    protected static ?string $pluralModelLabel = 'INVOICE STEG';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Facture numero')
                            ->placeholder('Ex: 107/2025')
                            ->default(static fn () => InvoiceSteg::getNextInvoiceNumber())
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->default(company_today()->toDateString())
                            ->required(),
                        Forms\Components\TextInput::make('invoice_city')
                            ->label('Ville')
                            ->default('Sfax')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('currency_code')
                            ->default('TND')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('client_name')
                            ->label('Destinataire')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('client_address')
                            ->label('Adresse destinataire')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('object')
                            ->label('OBJET')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('bon_de_commande')
                            ->label('Bon de commande')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Lines')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship('lines')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $quantity = max(InvoiceStegCalculator::toFloat($data['quantity'] ?? 0), 0);
                                $puht = max(InvoiceStegCalculator::toFloat($data['puht'] ?? 0), 0);

                                $data['quantity'] = InvoiceStegCalculator::round3($quantity);
                                $data['puht'] = InvoiceStegCalculator::round3($puht);
                                $data['ptht'] = InvoiceStegCalculator::round3($quantity * $puht);

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $quantity = max(InvoiceStegCalculator::toFloat($data['quantity'] ?? 0), 0);
                                $puht = max(InvoiceStegCalculator::toFloat($data['puht'] ?? 0), 0);

                                $data['quantity'] = InvoiceStegCalculator::round3($quantity);
                                $data['puht'] = InvoiceStegCalculator::round3($puht);
                                $data['ptht'] = InvoiceStegCalculator::round3($quantity * $puht);

                                return $data;
                            })
                            ->reorderable()
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('CODE')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('designation')
                                    ->label('DESIGNATION')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('QTE')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->live(),
                                Forms\Components\TextInput::make('unit')
                                    ->label('UNITE')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('puht')
                                    ->label('PUHT')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->live(),
                                Forms\Components\Placeholder::make('ptht_preview')
                                    ->label('PTHT')
                                    ->content(function (Forms\Get $get) {
                                        $quantity = InvoiceStegCalculator::toFloat($get('quantity'));
                                        $puht = InvoiceStegCalculator::toFloat($get('puht'));

                                        return number_format(InvoiceStegCalculator::round3($quantity * $puht), 3, '.', ' ');
                                    }),
                                Forms\Components\Hidden::make('ptht'),
                            ])
                            ->columns(6)
                            ->addActionLabel('Add line')
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Calcul')
                    ->schema([
                        Forms\Components\Placeholder::make('total_ht_preview')
                            ->label('TOTAL H.T')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['total_ht'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('tva_19_preview')
                            ->label('TVA 19%')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['tva_19'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('rg_5_preview')
                            ->label('RG 5%')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['rg_5'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('total_ttc_preview')
                            ->label('TOTAL T.T.C')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['total_ttc'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('retenue_source_1_preview')
                            ->label('Retenue a la source 1%')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['retenue_source_1'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('tva_25_preview')
                            ->label('25% de la TVA')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['tva_25'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('net_a_payer_preview')
                            ->label('NET A PAYER')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return number_format($totals['net_a_payer'], 3, '.', ' ');
                            }),
                        Forms\Components\Placeholder::make('amount_in_words_preview')
                            ->label('Montant en lettres')
                            ->content(function (Forms\Get $get) {
                                $totals = InvoiceStegCalculator::calculate($get('lines') ?? []);

                                return $totals['amount_in_words'];
                            })
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('total_ht'),
                        Forms\Components\Hidden::make('tva_19'),
                        Forms\Components\Hidden::make('rg_5'),
                        Forms\Components\Hidden::make('total_ttc'),
                        Forms\Components\Hidden::make('retenue_source_1'),
                        Forms\Components\Hidden::make('tva_25'),
                        Forms\Components\Hidden::make('net_a_payer'),
                        Forms\Components\Hidden::make('amount_in_words'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Facture #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_ht')
                    ->label('TOTAL H.T')
                    ->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('net_a_payer')
                    ->label('NET A PAYER')
                    ->numeric(decimalPlaces: 3),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->url(fn (InvoiceSteg $record) => route('invoice-stegs.print', ['invoiceSteg' => $record]))
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceStegs::route('/'),
            'create' => Pages\CreateInvoiceSteg::route('/create'),
            'edit' => Pages\EditInvoiceSteg::route('/{record}/edit'),
        ];
    }

    protected static function canAccessFor(string $permission): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
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
