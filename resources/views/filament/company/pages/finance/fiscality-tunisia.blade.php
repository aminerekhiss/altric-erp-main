<x-filament-panels::page>
    @php
        $fmt = static fn (float|int $value): string => number_format((float) $value, 3, '.', ' ') . ' TND';
    @endphp

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Fiscalite Tunisie</h2>
        <p class="mt-1 text-sm text-slate-500">Indicateurs fiscaux mensuels et reperes de conformite locale.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Grand Livre</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Clients</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fournisseurs</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tresorerie</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total H.T</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $fmt($totalHt) }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">TVA 19%</p>
            <p class="mt-1 text-3xl font-bold text-sky-900">{{ $fmt($tva19) }}</p>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">RG 5%</p>
            <p class="mt-1 text-3xl font-bold text-orange-900">{{ $fmt($rg5) }}</p>
        </div>
        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Retenue 1%</p>
            <p class="mt-1 text-3xl font-bold text-cyan-900">{{ $fmt($retenue1) }}</p>
        </div>
        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">25% TVA</p>
            <p class="mt-1 text-3xl font-bold text-violet-900">{{ $fmt($tva25) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Net a payer</p>
            <p class="mt-1 text-3xl font-bold text-emerald-900">{{ $fmt($netAPayer) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Conformite fiscale Tunisie</h3>
        <ul class="mt-3 grid gap-2 text-sm text-slate-700 md:grid-cols-2">
            <li class="rounded-md bg-slate-50 px-3 py-2">TVA standard: 19%</li>
            <li class="rounded-md bg-slate-50 px-3 py-2">TVA reduite: 13%</li>
            <li class="rounded-md bg-slate-50 px-3 py-2">TVA reduite: 7%</li>
            <li class="rounded-md bg-slate-50 px-3 py-2">Retenue a la source selon regime en vigueur</li>
            <li class="rounded-md bg-slate-50 px-3 py-2">RG et taxes sectorielles a parametrer par activite</li>
            <li class="rounded-md bg-slate-50 px-3 py-2">Preparation declarations mensuelles/annuelles</li>
        </ul>
    </div>
</x-filament-panels::page>
