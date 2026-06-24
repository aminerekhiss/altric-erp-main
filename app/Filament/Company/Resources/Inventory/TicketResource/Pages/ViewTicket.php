<?php

namespace App\Filament\Company\Resources\Inventory\TicketResource\Pages;

use App\Filament\Company\Resources\Inventory\TicketResource;
use App\Models\Common\Ticket;
use App\Services\TicketStockService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function canManageTicketActions(Ticket $record): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if ((int) $record->company_id !== (int) $company->id) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'update');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label(translate('Confirm'))
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record) => ! $record->isValidated() && $this->canManageTicketActions($record))
                ->action(function (Ticket $record) {
                    abort_unless($this->canManageTicketActions($record), 403, 'Unauthorized action.');

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
            Actions\Action::make('cancel')
                ->label(translate('Cancel'))
                ->icon('heroicon-m-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record) => $record->isValidated() && $this->canManageTicketActions($record))
                ->action(function (Ticket $record) {
                    abort_unless($this->canManageTicketActions($record), 403, 'Unauthorized action.');

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
            Actions\Action::make('reopen')
                ->label(translate('Reopen'))
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Ticket $record) => $record->status === Ticket::STATUS_CANCELLED && $this->canManageTicketActions($record))
                ->action(function (Ticket $record) {
                    abort_unless($this->canManageTicketActions($record), 403, 'Unauthorized action.');

                    $record->update([
                        'status' => Ticket::STATUS_DRAFT,
                    ]);
                })
                ->successNotificationTitle(translate('Ticket reopened as draft')),
            Actions\Action::make('print')
                ->label(translate('Print'))
                ->icon('heroicon-m-printer')
                ->url(fn (Ticket $record) => route('tickets.print', ['ticket' => $record]))
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ticket Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('type')
                            ->formatStateUsing(fn (string $state) => Ticket::getTypeOptions()[$state] ?? ucfirst($state)),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('product.name')
                            ->label(translate('Product')),
                        TextEntry::make('provider'),
                        TextEntry::make('invoice_from')
                            ->label(translate('Invoice from'))
                            ->placeholder('-'),
                        TextEntry::make('invoice_date')
                            ->label(translate('Invoice date'))
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('invoice_file')
                            ->label(translate('Invoice archive file'))
                            ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '-')
                            ->url(fn (Ticket $record) => $record->invoice_file ? Storage::url($record->invoice_file) : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('quantity'),
                        TextEntry::make('date')
                            ->date(),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
