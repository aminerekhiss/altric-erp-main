<?php

namespace App\Filament\Company\Resources\Sales\InvoiceStegResource\Pages;

use App\Filament\Company\Resources\Sales\InvoiceStegResource;
use App\Models\Common\InvoiceSteg;
use App\Support\InvoiceStegCalculator;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoiceSteg extends CreateRecord
{
    protected static string $resource = InvoiceStegResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
