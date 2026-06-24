<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\RelationManagers;

use App\Enums\Accounting\PaymentMethod;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $modelLabel = 'Payment';

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label(translate('Date')),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label(translate('Account'))
                            ->required()
                            ->live()
                            ->options(function () {
                                /** @var Bill $bill */
                                $bill = $this->getOwnerRecord();

                                return BankAccount::query()
                                    ->where('bank_accounts.company_id', $bill->company_id)
                                    ->join('accounts', 'bank_accounts.account_id', '=', 'accounts.id')
                                    ->select(['bank_accounts.id', 'accounts.name', 'accounts.currency_code'])
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        $label = $account->name;
                                        if ($account->currency_code) {
                                            $label .= " ({$account->currency_code})";
                                        }

                                        return [$account->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable(),
                        Forms\Components\TextInput::make('amount')
                            ->label(translate('Amount'))
                            ->required()
                            ->money(function (RelationManager $livewire) {
                                /** @var Bill $bill */
                                $bill = $livewire->getOwnerRecord();

                                return $bill->currency_code;
                            })
                            ->live(onBlur: true)
                            ->helperText(function (RelationManager $livewire, $state, ?Transaction $record) {
                                /** @var Bill $ownerRecord */
                                $ownerRecord = $livewire->getOwnerRecord();

                                $billCurrency = $ownerRecord->currency_code;

                                if (! CurrencyConverter::isValidAmount($state, 'USD')) {
                                    return null;
                                }

                                $amountDue = $ownerRecord->amount_due;

                                $amount = CurrencyConverter::convertToCents($state, 'USD');

                                if ($amount <= 0) {
                                    return translate('Please enter a valid positive amount');
                                }

                                $currentPaymentAmount = $record?->amount ?? 0;

                                $newAmountDue = $amountDue - $amount + $currentPaymentAmount;

                                return match (true) {
                                    $newAmountDue > 0 => translate('Amount due after payment will be') . ' ' . CurrencyConverter::formatCentsToMoney($newAmountDue, $billCurrency),
                                    $newAmountDue === 0 => translate('Bill will be fully paid'),
                                    default => translate('Amount exceeds bill total by') . ' ' . CurrencyConverter::formatCentsToMoney(abs($newAmountDue), $billCurrency),
                                };
                            })
                            ->rules([
                                static fn (): Closure => static function (string $attribute, $value, Closure $fail) {
                                    if (! CurrencyConverter::isValidAmount($value, 'USD')) {
                                        $fail(translate('Please enter a valid amount'));
                                    }
                                },
                            ]),
                    ])->columns(2),
                Forms\Components\Placeholder::make('currency_conversion')
                    ->label(translate('Currency Conversion'))
                    ->content(function (Forms\Get $get, RelationManager $livewire) {
                        $amount = $get('amount');
                        $bankAccountId = $get('bank_account_id');

                        /** @var Bill $bill */
                        $bill = $livewire->getOwnerRecord();
                        $billCurrency = $bill->currency_code;

                        if (empty($amount) || empty($bankAccountId) || ! CurrencyConverter::isValidAmount($amount, 'USD')) {
                            return null;
                        }

                        /** @var Bill $bill */
                        $bill = $livewire->getOwnerRecord();

                        $bankAccount = BankAccount::with('account')
                            ->where('company_id', $bill->company_id)
                            ->find($bankAccountId);
                        if (! $bankAccount) {
                            return null;
                        }

                        $bankCurrency = $bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency();

                        // If currencies are the same, no conversion needed
                        if ($billCurrency === $bankCurrency) {
                            return null;
                        }

                        // Convert amount from bill currency to bank currency
                        $amountInBillCurrencyCents = CurrencyConverter::convertToCents($amount, 'USD');
                        $amountInBankCurrencyCents = CurrencyConverter::convertBalance(
                            $amountInBillCurrencyCents,
                            $billCurrency,
                            $bankCurrency
                        );

                        $formattedBankAmount = CurrencyConverter::formatCentsToMoney($amountInBankCurrencyCents, $bankCurrency);

                        return translate('Payment will be recorded as') . " {$formattedBankAmount} " . translate('in the bank account currency') . " ({$bankCurrency}).";
                    })
                    ->hidden(function (Forms\Get $get, RelationManager $livewire) {
                        $bankAccountId = $get('bank_account_id');
                        if (empty($bankAccountId)) {
                            return true;
                        }

                        /** @var Bill $bill */
                        $bill = $livewire->getOwnerRecord();
                        $billCurrency = $bill->currency_code;

                        $bankAccount = BankAccount::with('account')
                            ->where('company_id', $bill->company_id)
                            ->find($bankAccountId);
                        if (! $bankAccount) {
                            return true;
                        }

                        $bankCurrency = $bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency();

                        // Hide if currencies are the same
                        return $billCurrency === $bankCurrency;
                    }),
                Forms\Components\Select::make('payment_method')
                    ->label(translate('Payment method'))
                    ->required()
                    ->options(PaymentMethod::class),
                Forms\Components\Textarea::make('notes')
                    ->label(translate('Notes')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label(translate('Date'))
                    ->sortable()
                    ->defaultDateFormat(),
                Tables\Columns\TextColumn::make('type')
                    ->label(translate('Type'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label(translate('Description'))
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bankAccount.account.name')
                    ->label(translate('Account'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(translate('Amount'))
                    ->weight(static fn (Transaction $transaction) => $transaction->reviewed ? null : FontWeight::SemiBold)
                    ->color(
                        static fn (Transaction $transaction) => match ($transaction->type) {
                            TransactionType::Deposit => Color::rgb('rgb(' . Color::Green[700] . ')'),
                            TransactionType::Journal => 'primary',
                            default => null,
                        }
                    )
                    ->sortable()
                    ->currency(static fn (Transaction $transaction) => $transaction->bankAccount?->account->currency_code ?? CurrencyAccessor::getDefaultCurrency()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(translate('Record payment'))
                    ->modalHeading(fn (Tables\Actions\CreateAction $action) => $action->getLabel())
                    ->slideOver()
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->visible(function () {
                        return $this->getOwnerRecord()->canRecordPayment();
                    })
                    ->mountUsing(function (Form $form) {
                        $record = $this->getOwnerRecord();
                        $form->fill([
                            'posted_at' => company_today()->toDateString(),
                            'amount' => $record->amount_due,
                        ]);
                    })
                    ->databaseTransaction()
                    ->successNotificationTitle(translate('Payment recorded'))
                    ->action(function (Tables\Actions\CreateAction $action, array $data) {
                        /** @var Bill $record */
                        $record = $this->getOwnerRecord();

                        $bankAccount = BankAccount::query()
                            ->where('company_id', $record->company_id)
                            ->find((int) ($data['bank_account_id'] ?? 0));

                        if (! $bankAccount) {
                            throw ValidationException::withMessages([
                                'data.bank_account_id' => translate('Selected bank account is invalid for this company.'),
                            ]);
                        }

                        $data['bank_account_id'] = (int) $bankAccount->id;

                        $record->recordPayment($data);

                        $action->success();

                        $this->dispatch('refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->after(fn () => $this->dispatch('refresh')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
