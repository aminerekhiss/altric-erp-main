<?php

namespace App\Http\Controllers;

use App\Models\Common\ParametrableInvoice;
use App\Support\ParametrableInvoiceCalculator;
use Illuminate\Support\Facades\Auth;

class ParametrableInvoicePrintController extends Controller
{
    public function show(ParametrableInvoice $parametrableInvoice)
    {
        abort_unless(
            (int) $parametrableInvoice->company_id === (int) Auth::user()?->current_company_id,
            403,
            'Unauthorized invoice access.'
        );

        $parametrableInvoice->load(['lines.product', 'adjustments', 'company.profile']);

        $computed = ParametrableInvoiceCalculator::calculate(
            $parametrableInvoice->lines->map(function ($line) {
                return [
                    'quantity' => $line->quantity,
                    'puht' => $line->puht,
                ];
            })->toArray(),
            $parametrableInvoice->adjustments->map(function ($adjustment) {
                return [
                    'operation' => $adjustment->operation,
                    'percentage' => $adjustment->percentage,
                ];
            })->toArray(),
        );

        $printAdjustments = $parametrableInvoice->adjustments->values()->map(function ($adjustment) use ($computed) {
            $percentage = ParametrableInvoiceCalculator::toFloat($adjustment->percentage);
            $amount = ParametrableInvoiceCalculator::round3($computed['total_ht'] * ($percentage / 100));

            return [
                'label' => $adjustment->label,
                'operation' => $adjustment->operation,
                'percentage' => $percentage,
                'amount' => $amount,
            ];
        })->toArray();

        $parametrableInvoice->updateQuietly([
            'total_ht' => $computed['total_ht'],
            'adjustments_total' => $computed['adjustments_total'],
            'net_ht' => $computed['net_ht'],
            'amount_in_words' => $computed['amount_in_words'],
        ]);

        return view('print-parametrable-invoice', [
            'invoice' => $parametrableInvoice,
            'computed' => $computed,
            'printAdjustments' => $printAdjustments,
        ]);
    }
}
