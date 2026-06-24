<x-filament-panels::page>
    @php
        $fmt = static fn (float|int $value): string => number_format((float) $value, 3, '.', ' ') . ' TND';

        $financeSections = [
            ['label' => 'Comptabilite Generale', 'desc' => 'Journal, grand livre, balance et ecritures.', 'url' => \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl()],
            ['label' => 'Comptabilite Clients', 'desc' => 'Encours clients, impayes et recouvrement.', 'url' => \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl()],
            ['label' => 'Comptabilite Fournisseurs', 'desc' => 'Dettes fournisseurs et echeances a payer.', 'url' => \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl()],
            ['label' => 'Tresorerie', 'desc' => 'Flux bancaires, disponibilites et prevision.', 'url' => \App\Filament\Company\Pages\Finance\Treasury::getUrl()],
            ['label' => 'Fiscalite Tunisie', 'desc' => 'TVA, retenue, RG et conformite locale.', 'url' => \App\Filament\Company\Pages\Finance\FiscalityTunisia::getUrl()],
            ['label' => 'Analyses et Predictions', 'desc' => 'Graphiques reels et projections de ventes/achats.', 'url' => \App\Filament\Company\Pages\Finance\AnalysesPredictions::getUrl()],
        ];
    @endphp

    <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        @foreach($financeSections as $section)
            <a href="{{ $section['url'] }}" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-sm font-semibold text-slate-900">{{ $section['label'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $section['desc'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total H.T (Mois)</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $fmt($kpis['total_ht'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">TVA 19% (Mois)</p>
            <p class="mt-1 text-2xl font-bold text-sky-900">{{ $fmt($kpis['tva_19'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Net a payer (Mois)</p>
            <p class="mt-1 text-2xl font-bold text-violet-900">{{ $fmt($kpis['net_a_payer'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Couts voitures (Mois)</p>
            <p class="mt-1 text-2xl font-bold text-amber-900">{{ $fmt($kpis['monthly_car_costs'] ?? 0) }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-2">
            <h3 class="text-base font-semibold text-slate-900">Actions rapides</h3>
            <p class="mt-1 text-sm text-slate-500">Flux quotidien finance sans ecran surcharge.</p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl() }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100">Grand Livre</a>
                <a href="{{ \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl() }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100">Clients (AR)</a>
                <a href="{{ \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl() }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100">Fournisseurs (AP)</a>
                <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100">Tresorerie</a>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Echeances 30 jours</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format(count($upcomingLegalDeadlines)) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes finance recentes</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($recentFinanceNotes->count()) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-base font-semibold text-slate-900">Regles fiscales Tunisie</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                <li class="rounded-md bg-slate-50 px-3 py-2">TVA: 19%</li>
                <li class="rounded-md bg-slate-50 px-3 py-2">RG: 5% du H.T.</li>
                <li class="rounded-md bg-slate-50 px-3 py-2">Retenue a la source: 1% du T.T.C.</li>
                <li class="rounded-md bg-slate-50 px-3 py-2">25% de TVA deductible (selon vos regles internes)</li>
            </ul>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-base font-semibold text-slate-900">Echeances legales voitures (30 jours)</h3>
            <div class="mt-3 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Voiture</th>
                        <th class="pb-2">Type</th>
                        <th class="pb-2">Date</th>
                        <th class="pb-2 text-right">Montant</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($upcomingLegalDeadlines as $item)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $item['car_number'] }}</td>
                            <td class="py-2">{{ $item['type'] }}</td>
                            <td class="py-2">{{ optional($item['date'])->format('d/m/Y') }}</td>
                            <td class="py-2 text-right">{{ $fmt((float) ($item['amount'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-3 text-slate-500">Aucune echeance sur les 30 prochains jours.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-base font-semibold text-slate-900">Derniers couts voitures</h3>
            <div class="mt-3 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Voiture</th>
                        <th class="pb-2">Type</th>
                        <th class="pb-2">Date</th>
                        <th class="pb-2 text-right">Montant</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentCarCosts as $cost)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">{{ $cost->car?->car_number }}</td>
                            <td class="py-2">{{ ucfirst((string) $cost->cost_type) }}</td>
                            <td class="py-2">{{ $cost->cost_date?->format('d/m/Y') }}</td>
                            <td class="py-2 text-right">{{ $fmt((float) ($cost->amount ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-3 text-slate-500">Aucun cout enregistre.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Finance Notes</h3>
        <p class="mt-1 text-sm text-slate-500">Notes analytiques pour fiscalite, paiements et suivi financier.</p>
        <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="pb-2">Date</th>
                    <th class="pb-2">Category</th>
                    <th class="pb-2">Title</th>
                    <th class="pb-2">Note</th>
                    <th class="pb-2 text-right">Amount</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentFinanceNotes as $note)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="py-2">{{ $note->note_date?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ ucfirst((string) $note->category) }}</td>
                        <td class="py-2 font-medium">{{ $note->title }}</td>
                        <td class="py-2 text-slate-600">{{ $note->note }}</td>
                        <td class="py-2 text-right">{{ is_null($note->amount) ? '-' : $fmt((float) $note->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-3 text-slate-500">No finance notes yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
