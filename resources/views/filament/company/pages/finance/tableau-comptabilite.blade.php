<x-filament-panels::page>
    @php
        $fmt = static fn (float|int|string|null $value): string => number_format((float) ($value ?? 0), 3, '.', ' ') . ' TND';
    @endphp

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Tableau comptabilite</h2>
        <p class="mt-1 text-sm text-slate-500">Chaque methode de paiement a son tableau: solde initial + debit - credit = resultat.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Echance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Echance</a>
    </div>

    <div class="space-y-4">
        @foreach($methodTables as $table)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $table['name'] }}</h3>
                        <p class="text-xs text-slate-500">Table code: {{ $table['code'] }}</p>
                    </div>

                    <form wire:submit.prevent="saveInitialBalance('{{ $table['method'] }}')" class="flex items-end gap-2">
                        <label class="block">
                            <span class="text-xs text-slate-500">Solde initial</span>
                            <input
                                type="number"
                                step="0.001"
                                wire:model.defer="initialBalances.{{ $table['method'] }}"
                                class="mt-1 w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                            />
                        </label>
                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Enregistrer</button>
                    </form>
                </div>

                <div class="mt-3 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-slate-500">
                            <th class="pb-2">Date</th>
                            <th class="pb-2">Reference</th>
                            <th class="pb-2">Fournisseur</th>
                            <th class="pb-2 text-right">Debit</th>
                            <th class="pb-2 text-right">Credit</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($table['rows'] as $row)
                            <tr class="border-t border-slate-100">
                                <td class="py-2">{{ $row->echance_date?->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $row->reference ?: '-' }}</td>
                                <td class="py-2">{{ $row->supplier ?: '-' }}</td>
                                <td class="py-2 text-right text-emerald-700">{{ $row->entry_type === 'entree' ? $fmt($row->amount) : '-' }}</td>
                                <td class="py-2 text-right text-rose-700">{{ $row->entry_type === 'sortie' ? $fmt($row->amount) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-slate-500">Aucune operation payee pour cette methode.</td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50">
                            <td class="py-2 font-semibold" colspan="3">Totaux</td>
                            <td class="py-2 text-right font-semibold text-emerald-700">{{ $fmt($table['debit_total']) }}</td>
                            <td class="py-2 text-right font-semibold text-rose-700">{{ $fmt($table['credit_total']) }}</td>
                        </tr>
                        <tr class="border-t border-slate-200">
                            <td class="py-2" colspan="3">Resultat = Solde initial + Debit - Credit</td>
                            <td class="py-2 text-right" colspan="2">
                                <span class="font-semibold {{ $table['result'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $fmt($table['result']) }}</span>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
