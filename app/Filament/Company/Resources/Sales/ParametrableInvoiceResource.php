<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ParametrableInvoiceResource\Pages;
use App\Filament\Tables\Columns;
use App\Models\Common\ParametrableInvoice;
use App\Models\Common\Product;
use App\Support\ParametrableInvoiceCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ParametrableInvoiceResource extends Resource
{
    protected static ?string $model = ParametrableInvoice::class;

    protected static ?string $modelLabel = 'Parametrable Invoice';

    protected static ?string $pluralModelLabel = 'Parametrable Invoices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(translate('Invoice Information'))
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label(translate('Invoice number'))
                            ->required(),
                        Forms\Components\TextInput::make('client_name')
                            ->label(translate('Client'))
                            ->required(),
                        Forms\Components\TextInput::make('object')
                            ->label(translate('Object'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('currency_code')
                            ->label(translate('Currency'))
                            ->default('TND')
                            ->maxLength(10),
                        Forms\Components\Toggle::make('is_structure')
                            ->label(translate('Save as structure template'))
                            ->default(false)
                            ->live(),
                        Forms\Components\TextInput::make('structure_name')
                            ->label(translate('Structure name'))
                            ->maxLength(255)
                            ->hidden(fn (Forms\Get $get) => ! (bool) $get('is_structure')),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(translate('Lines'))
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('entry_mode')
                                    ->label(translate('Entry mode'))
                                    ->options([
                                        'select' => translate('Select product'),
                                        'new' => translate('Write new item'),
                                    ])
                                    ->default('select')
                                    ->live(),
                                Forms\Components\Select::make('product_id')
                                    ->label(translate('Product'))
                                    ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->searchable()
                                    ->hidden(fn (Forms\Get $get) => ($get('entry_mode') ?? 'select') === 'new')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = Product::query()->find($state);

                                        if ($product) {
                                            $set('designation', $product->name);
                                        }
                                    }),
                                Forms\Components\TextInput::make('designation')
                                    ->label(translate('Designation'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('unit')
                                    ->label(translate('Unit'))
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(translate('Quantity'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->live(),
                                Forms\Components\TextInput::make('puht')
                                    ->label(translate('Price (H.T)'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->live(),
                                Forms\Components\Placeholder::make('ptht_preview')
                                    ->label(translate('Line Total (H.T)'))
                                    ->content(function (Forms\Get $get) {
                                        $quantity = ParametrableInvoiceCalculator::toFloat($get('quantity'));
                                        $puht = ParametrableInvoiceCalculator::toFloat($get('puht'));

                                        return number_format(ParametrableInvoiceCalculator::round3($quantity * $puht), 3, '.', ' ');
                                    }),
                            ])
                            ->columns(4)
                            ->addActionLabel(translate('Add line'))
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(translate('Invoice #'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_name')
                    ->label(translate('Client'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_structure')
                    ->label(translate('Structure'))
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListParametrableInvoices::route('/'),
            'create' => Pages\CreateParametrableInvoice::route('/create'),
            'edit' => Pages\EditParametrableInvoice::route('/{record}/edit'),
        ];
    }
}
