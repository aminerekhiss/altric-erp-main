<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Comptabilite Generale</h2>
        <p class="mt-1 text-sm text-slate-500">Vue synthese des ecritures, transactions et activite comptable recente.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Clients</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fournisseurs</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tresorerie</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\FiscalityTunisia::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fiscalite</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Comptes</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($accountsCount) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ecritures Journal</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($entriesCount) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Flux recents</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($recentTransactions->count()) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Dernieres transactions</h3>
        <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="pb-2">Date</th>
                    <th class="pb-2">Type</th>
                    <th class="pb-2">Description</th>
                    <th class="pb-2 text-right">Montant</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentTransactions as $tx)
                    @php
                        $txType = $tx->type;
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $tx->posted_at?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ $txType instanceof \App\Enums\Accounting\TransactionType ? $txType->getLabel() : ucfirst((string) $txType) }}</td>
                        <td class="py-2">{{ $tx->description }}</td>
                        <td class="py-2 text-right">{{ number_format((float) $tx->amount, 3, '.', ' ') }} TND</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-slate-500">Aucune transaction.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
