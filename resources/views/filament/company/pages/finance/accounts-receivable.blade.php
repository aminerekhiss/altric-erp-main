<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Comptabilite Clients (AR)</h2>
        <p class="mt-1 text-sm text-slate-500">Encours, impayes et dernieres factures clients sur une seule vue.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Grand Livre</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fournisseurs</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tresorerie</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\FiscalityTunisia::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fiscalite</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Encours clients</p>
            <p class="mt-1 text-3xl font-bold text-blue-900">{{ number_format((float) $totalOpen, 3, '.', ' ') }} TND</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Impayes en retard</p>
            <p class="mt-1 text-3xl font-bold text-rose-900">{{ number_format((float) $totalOverdue, 3, '.', ' ') }} TND</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Factures clients recentes</h3>
        <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="pb-2">Facture</th>
                    <th class="pb-2">Date</th>
                    <th class="pb-2">Statut</th>
                    <th class="pb-2 text-right">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentInvoices as $invoice)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $invoice->invoice_number }}</td>
                        <td class="py-2">{{ $invoice->date?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ $invoice->status?->getLabel() ?? $invoice->status }}</td>
                        <td class="py-2 text-right">{{ number_format((float) $invoice->total, 2, '.', ' ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-slate-500">Aucune facture.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
