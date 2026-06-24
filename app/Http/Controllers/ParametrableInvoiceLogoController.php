<?php

namespace App\Http\Controllers;

use App\Models\Common\ParametrableInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParametrableInvoiceLogoController extends Controller
{
    public function edit(ParametrableInvoice $parametrableInvoice): View
    {
        abort_unless(
            (int) $parametrableInvoice->company_id === (int) Auth::user()?->current_company_id,
            403,
            'Unauthorized invoice access.'
        );

        return view('parametrable-invoice-logo-upload', [
            'invoice' => $parametrableInvoice,
        ]);
    }

    public function update(Request $request, ParametrableInvoice $parametrableInvoice): RedirectResponse
    {
        abort_unless(
            (int) $parametrableInvoice->company_id === (int) Auth::user()?->current_company_id,
            403,
            'Unauthorized invoice access.'
        );

        $validated = $request->validate([
            'logo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        if ($parametrableInvoice->print_logo && Storage::disk('public')->exists($parametrableInvoice->print_logo)) {
            Storage::disk('public')->delete($parametrableInvoice->print_logo);
        }

        $path = $validated['logo']->store('logos/parametrable-invoices', 'public');

        $parametrableInvoice->update([
            'print_logo' => $path,
        ]);

        return redirect()
            ->route('parametrable-invoices.logo.edit', ['parametrableInvoice' => $parametrableInvoice])
            ->with('status', 'Logo uploaded successfully.');
    }

    public function destroy(ParametrableInvoice $parametrableInvoice): RedirectResponse
    {
        abort_unless(
            (int) $parametrableInvoice->company_id === (int) Auth::user()?->current_company_id,
            403,
            'Unauthorized invoice access.'
        );

        if ($parametrableInvoice->print_logo && Storage::disk('public')->exists($parametrableInvoice->print_logo)) {
            Storage::disk('public')->delete($parametrableInvoice->print_logo);
        }

        $parametrableInvoice->update([
            'print_logo' => null,
        ]);

        return redirect()
            ->route('parametrable-invoices.logo.edit', ['parametrableInvoice' => $parametrableInvoice])
            ->with('status', 'Logo removed.');
    }
}
