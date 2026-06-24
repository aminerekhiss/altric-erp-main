<?php

namespace App\Filament\Company\Resources\Sales\InvoiceStegResource\Pages;

use App\Filament\Company\Resources\Sales\InvoiceStegResource;
use App\Models\Common\InvoiceSteg;
use App\Support\InvoiceStegCalculator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceSteg extends EditRecord
{
    protected static string $resource = InvoiceStegResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->url(fn () => route('invoice-stegs.print', ['invoiceSteg' => $this->record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = InvoiceSteg::getNextInvoiceNumber();
        }

        $totals = InvoiceStegCalculator::calculate($data['lines'] ?? []);

        $data['lines'] = $totals['lines'];
        $data['total_ht'] = $totals['total_ht'];
        $data['tva_19'] = $totals['tva_19'];
        $data['rg_5'] = $totals['rg_5'];
        $data['total_ttc'] = $totals['total_ttc'];
        $data['retenue_source_1'] = $totals['retenue_source_1'];
        $data['tva_25'] = $totals['tva_25'];
        $data['net_a_payer'] = $totals['net_a_payer'];
        $data['amount_in_words'] = $totals['amount_in_words'];

        return $data;
    }
}
