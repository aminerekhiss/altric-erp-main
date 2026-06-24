<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Comptabilite Fournisseurs (AP)</h2>
        <p class="mt-1 text-sm text-slate-500">Suivi des dettes, echeances proches et dernieres factures fournisseurs.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Grand Livre</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Clients</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tresorerie</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\FiscalityTunisia::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fiscalite</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Dettes fournisseurs</p>
            <p class="mt-1 text-3xl font-bold text-amber-900">{{ number_format((float) $totalOutstanding, 3, '.', ' ') }} TND</p>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">A payer (15 jours)</p>
            <p class="mt-1 text-3xl font-bold text-orange-900">{{ number_format((float) $totalDueSoon, 3, '.', ' ') }} TND</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Factures fournisseurs recentes</h3>
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
                @forelse($recentBills as $bill)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $bill->bill_number }}</td>
                        <td class="py-2">{{ $bill->date?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ $bill->status?->getLabel() ?? $bill->status }}</td>
                        <td class="py-2 text-right">{{ number_format((float) $bill->total, 2, '.', ' ') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-slate-500">Aucune facture.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
