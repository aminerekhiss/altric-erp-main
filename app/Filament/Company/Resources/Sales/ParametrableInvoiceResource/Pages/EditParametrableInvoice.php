<?php

namespace App\Filament\Company\Resources\Sales\ParametrableInvoiceResource\Pages;

use App\Filament\Company\Resources\Sales\ParametrableInvoiceResource;
use App\Models\Common\ParametrableInvoice;
use App\Support\ParametrableInvoiceCalculator;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditParametrableInvoice extends EditRecord
{
    protected static string $resource = ParametrableInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('logoUploader')
                ->label('Logo Uploader')
                ->icon('heroicon-m-photo')
                ->url(fn () => route('parametrable-invoices.logo.edit', ['parametrableInvoice' => $this->record]))
                ->openUrlInNewTab(),
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->url(fn () => route('parametrable-invoices.print', ['parametrableInvoice' => $this->record]))
                ->openUrlInNewTab(),
            Actions\Action::make('saveAsStructure')
                ->label('Save structure')
                ->icon('heroicon-m-bookmark-square')
                ->form([
                    \Filament\Forms\Components\TextInput::make('structure_name')
                        ->label('Structure name')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    /** @var ParametrableInvoice $replica */
                    $replica = $this->record->replicate();
                    $replica->is_structure = true;
                    $replica->structure_name = $data['structure_name'];
                    $replica->invoice_number = null;
                    $replica->client_name = null;
                    $replica->setAttribute('date', company_today());
                    $replica->print_logo = $this->record->print_logo;
                    $replica->print_header = $this->record->print_header;
                    $replica->print_footer = $this->record->print_footer;
                    $replica->save();

                    foreach ($this->record->lines as $line) {
                        $lineReplica = $line->replicate();
                        $lineReplica->parametrable_invoice_id = $replica->id;
                        $lineReplica->save();
                    }

                    foreach ($this->record->adjustments as $adjustment) {
                        $adjustmentReplica = $adjustment->replicate();
                        $adjustmentReplica->parametrable_invoice_id = $replica->id;
                        $adjustmentReplica->save();
                    }

                    Notification::make()
                        ->success()
                        ->title('Structure saved successfully')
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['print_logo']) && $this->record?->print_logo) {
            $data['print_logo'] = $this->record->print_logo;
        }

        $totals = ParametrableInvoiceCalculator::calculate(
            $data['lines'] ?? [],
            $data['adjustments'] ?? []
        );

        $data['lines'] = $totals['lines'];
        $data['adjustments'] = $totals['adjustments'];
        $data['total_ht'] = $totals['total_ht'];
        $data['adjustments_total'] = $totals['adjustments_total'];
        $data['net_ht'] = $totals['net_ht'];
        $data['amount_in_words'] = $totals['amount_in_words'];

        return $data;
    }
}
