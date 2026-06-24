<?php

namespace App\Http\Controllers;

use App\Models\Common\InvoiceSteg;
use App\Support\InvoiceStegCalculator;
use Illuminate\Support\Facades\Auth;

class InvoiceStegPrintController extends Controller
{
    public function show(InvoiceSteg $invoiceSteg)
    {
        abort_unless(
            (int) $invoiceSteg->company_id === (int) Auth::user()?->current_company_id,
            403,
            'Unauthorized invoice access.'
        );

        $invoiceSteg->load(['lines']);

        $computed = InvoiceStegCalculator::calculate(
            $invoiceSteg->lines->map(function ($line) {
                return [
                    'code' => $line->code,
                    'designation' => $line->designation,
                    'unit' => $line->unit,
                    'quantity' => $line->quantity,
                    'puht' => $line->puht,
                ];
            })->toArray()
        );

        $invoiceSteg->updateQuietly([
            'total_ht' => $computed['total_ht'],
            'tva_19' => $computed['tva_19'],
            'rg_5' => $computed['rg_5'],
            'total_ttc' => $computed['total_ttc'],
            'retenue_source_1' => $computed['retenue_source_1'],
            'tva_25' => $computed['tva_25'],
            'net_a_payer' => $computed['net_a_payer'],
            'amount_in_words' => $computed['amount_in_words'],
        ]);

        return view('print-invoice-steg', [
            'invoice' => $invoiceSteg,
            'computed' => $computed,
        ]);
    }
}
