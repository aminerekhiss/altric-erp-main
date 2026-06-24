<x-filament-panels::page>
    @php
        $fmt = static fn (float|int|string|null $value): string => number_format((float) ($value ?? 0), 3, '.', ' ') . ' TND';
    @endphp

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-base font-semibold text-slate-900">Tableau Echance</h2>
        <p class="mt-1 text-sm text-slate-500">Gestion des operations Entree/Sortie, solde initial, et statut Paye/Non paye.</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ \App\Filament\Company\Pages\Finance::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finance Overview</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\GeneralLedger::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Grand Livre</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsReceivable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Clients</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\AccountsPayable::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fournisseurs</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\Treasury::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tresorerie</a>
        <a href="{{ \App\Filament\Company\Pages\Finance\FiscalityTunisia::getUrl() }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fiscalite</a>
    </div>

    <div class="mb-4 grid gap-4 md:grid-cols-3">
        <form wire:submit.prevent="saveInitialBalance" class="rounded-xl border border-slate-200 bg-white p-4 md:col-span-2">
            <h3 class="text-sm font-semibold text-slate-900">Solde initial</h3>
            <p class="mt-1 text-xs text-slate-500">Ce solde est la base de calcul de votre tableau d'echance.</p>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block">
                    <span class="text-xs text-slate-500">Montant</span>
                    <input
                        type="number"
                        step="0.001"
                        wire:model.defer="initialBalanceInput"
                        class="mt-1 w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                    />
                </label>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Enregistrer</button>
            </div>
        </form>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Solde courant</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $fmt($currentBalance) }}</p>
            <p class="mt-1 text-xs text-emerald-700">Solde initial: {{ $fmt($initialBalance) }}</p>
        </div>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Ajouter une operation manuelle</h3>
        <p class="mt-1 text-sm text-slate-500">Ajoutez une ligne directement dans le tableau Echance.</p>

        <form wire:submit.prevent="createManualEchance" class="mt-3 grid gap-3 md:grid-cols-6">
            <label class="block">
                <span class="text-xs text-slate-500">Type</span>
                <select wire:model="manualEntryType" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="entree">Entree</option>
                    <option value="sortie">Sortie</option>
                </select>
            </label>

            <label class="block">
                <span class="text-xs text-slate-500">Montant</span>
                <input type="number" step="0.001" min="0" wire:model="manualAmount" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
            </label>

            <label class="block">
                <span class="text-xs text-slate-500">Date</span>
                <input type="date" wire:model="manualDate" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
            </label>

            <label class="block">
                <span class="text-xs text-slate-500">Fournisseur</span>
                <input type="text" wire:model="manualSupplier" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
            </label>

            <label class="block">
                <span class="text-xs text-slate-500">Reference</span>
                <input type="text" wire:model="manualReference" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
            </label>

            <div class="flex flex-col justify-end gap-2">
                <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                    <input type="checkbox" wire:model="manualIsPaid" />
                    Paye
                </label>
                @if($manualIsPaid)
                    <select wire:model="manualPaymentMethod" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs">
                        @foreach($this->paymentMethodOptions() as $methodValue => $methodLabel)
                            <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Ajouter</button>
            </div>
        </form>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Sources factures (normal, parametrable, recurring, archive)</h3>
        <p class="mt-1 text-sm text-slate-500">Selectionnez Entree/Sortie, ajustez le montant si besoin, puis creez l'echance.</p>

        <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="pb-2">Type source</th>
                    <th class="pb-2">Reference</th>
                    <th class="pb-2">Date facture</th>
                    <th class="pb-2">Fournisseur</th>
                    <th class="pb-2 text-right">Montant</th>
                    <th class="pb-2">Operation</th>
                    <th class="pb-2">Date echance</th>
                    <th class="pb-2">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sourceInvoices as $row)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="py-2">{{ $row['source_label'] }}</td>
                        <td class="py-2">{{ $row['reference'] }}</td>
                        <td class="py-2">{{ $row['document_date'] ? \Illuminate\Support\Carbon::parse($row['document_date'])->format('d/m/Y') : '-' }}</td>
                        <td class="py-2">{{ $row['supplier'] ?: '-' }}</td>
                        <td class="py-2 text-right">{{ $fmt($row['amount']) }}</td>
                        <td class="py-2">
                            @if(($row['source_type'] ?? '') === 'invoice_archive')
                                <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800">Sortie</span>
                            @else
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Entree</span>
                            @endif
                        </td>
                        <td class="py-2">
                            <input
                                type="date"
                                wire:model="sourceDrafts.{{ $row['key'] }}.echance_date"
                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                            />
                        </td>
                        <td class="py-2">
                            <div class="flex flex-col gap-2">
                                <input
                                    type="number"
                                    step="0.001"
                                    wire:model="sourceDrafts.{{ $row['key'] }}.amount"
                                    class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                                />
                                <input
                                    type="text"
                                    wire:model="sourceDrafts.{{ $row['key'] }}.supplier"
                                    placeholder="Fournisseur"
                                    class="w-36 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                                />
                                <button
                                    type="button"
                                    wire:click="createEchance('{{ $row['key'] }}')"
                                    class="rounded-lg bg-slate-900 px-2 py-1 text-xs font-semibold text-white hover:bg-slate-800"
                                >
                                    Creer echance
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-3 text-slate-500">Aucune source facture disponible.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4 grid gap-4 xl:grid-cols-2">
        @php
            $nonPayeRows = $echances->filter(fn ($item) => ! (bool) $item->is_paid);
            $payeRows = $echances->filter(fn ($item) => (bool) $item->is_paid);
        @endphp

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-base font-semibold text-slate-900">Tableau Non paye</h3>
            <div class="mt-3 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Type</th>
                        <th class="pb-2">Montant</th>
                        <th class="pb-2">Date</th>
                        <th class="pb-2">Fournisseur</th>
                        <th class="pb-2">Reference</th>
                        <th class="pb-2">Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($nonPayeRows as $echance)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="non_paye_entry_type_{{ $echance->id }}" wire:click="setEntryType({{ $echance->id }}, 'entree')" @checked($echance->entry_type === 'entree') />
                                        Entree
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="non_paye_entry_type_{{ $echance->id }}" wire:click="setEntryType({{ $echance->id }}, 'sortie')" @checked($echance->entry_type === 'sortie') />
                                        Sortie
                                    </label>
                                </div>
                            </td>
                            <td class="py-2 font-semibold {{ $this->amountColorClass($echance) }}">{{ $fmt($echance->amount) }}</td>
                            <td class="py-2">{{ $echance->echance_date?->format('d/m/Y') }}</td>
                            <td class="py-2">{{ $echance->supplier ?: '-' }}</td>
                            <td class="py-2">{{ $echance->reference ?: '-' }}</td>
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="non_paye_paid_status_{{ $echance->id }}" wire:click="setPaymentStatus({{ $echance->id }}, true)" @checked($echance->is_paid) />
                                        Paye
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="non_paye_paid_status_{{ $echance->id }}" wire:click="setPaymentStatus({{ $echance->id }}, false)" @checked(! $echance->is_paid) />
                                        Non paye
                                    </label>
                                </div>
                                @if($echance->is_paid)
                                    <select
                                        wire:change="setPaymentMethod({{ $echance->id }}, $event.target.value)"
                                        class="mt-2 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                                    >
                                        @foreach($this->paymentMethodOptions() as $methodValue => $methodLabel)
                                            <option value="{{ $methodValue }}" @selected(($echance->payment_method ?? 'cash') === $methodValue)>{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-slate-500">Aucune operation non payee.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="text-base font-semibold text-slate-900">Tableau Paye</h3>
            <div class="mt-3 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Type</th>
                        <th class="pb-2">Montant</th>
                        <th class="pb-2">Date</th>
                        <th class="pb-2">Fournisseur</th>
                        <th class="pb-2">Reference</th>
                        <th class="pb-2">Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payeRows as $echance)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="paye_entry_type_{{ $echance->id }}" wire:click="setEntryType({{ $echance->id }}, 'entree')" @checked($echance->entry_type === 'entree') />
                                        Entree
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="paye_entry_type_{{ $echance->id }}" wire:click="setEntryType({{ $echance->id }}, 'sortie')" @checked($echance->entry_type === 'sortie') />
                                        Sortie
                                    </label>
                                </div>
                            </td>
                            <td class="py-2 font-semibold {{ $this->amountColorClass($echance) }}">{{ $fmt($echance->amount) }}</td>
                            <td class="py-2">{{ $echance->echance_date?->format('d/m/Y') }}</td>
                            <td class="py-2">{{ $echance->supplier ?: '-' }}</td>
                            <td class="py-2">{{ $echance->reference ?: '-' }}</td>
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="paye_paid_status_{{ $echance->id }}" wire:click="setPaymentStatus({{ $echance->id }}, true)" @checked($echance->is_paid) />
                                        Paye
                                    </label>
                                    <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                        <input type="radio" name="paye_paid_status_{{ $echance->id }}" wire:click="setPaymentStatus({{ $echance->id }}, false)" @checked(! $echance->is_paid) />
                                        Non paye
                                    </label>
                                </div>
                                @if($echance->is_paid)
                                    <select
                                        wire:change="setPaymentMethod({{ $echance->id }}, $event.target.value)"
                                        class="mt-2 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                                    >
                                        @foreach($this->paymentMethodOptions() as $methodValue => $methodLabel)
                                            <option value="{{ $methodValue }}" @selected(($echance->payment_method ?? 'cash') === $methodValue)>{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-slate-500">Aucune operation payee.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h3 class="text-base font-semibold text-slate-900">Tableau Echance</h3>

        <div class="mt-3 overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="pb-2">Type Entree/Sortie</th>
                    <th class="pb-2">Montant</th>
                    <th class="pb-2">Date</th>
                    <th class="pb-2">Fournisseur</th>
                    <th class="pb-2">Reference</th>
                    <th class="pb-2">Statut</th>
                    <th class="pb-2 text-right">Solde apres operation</th>
                </tr>
                </thead>
                <tbody>
                @forelse($echances as $echance)
                    <tr class="border-t border-slate-100">
                        <td class="py-2">
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                    <input
                                        type="radio"
                                        name="entry_type_{{ $echance->id }}"
                                        wire:click="setEntryType({{ $echance->id }}, 'entree')"
                                        @checked($echance->entry_type === 'entree')
                                    />
                                    Entree
                                </label>
                                <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                    <input
                                        type="radio"
                                        name="entry_type_{{ $echance->id }}"
                                        wire:click="setEntryType({{ $echance->id }}, 'sortie')"
                                        @checked($echance->entry_type === 'sortie')
                                    />
                                    Sortie
                                </label>
                            </div>
                        </td>
                        <td class="py-2 font-semibold {{ $this->amountColorClass($echance) }}">{{ $fmt($echance->amount) }}</td>
                        <td class="py-2">{{ $echance->echance_date?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ $echance->supplier ?: '-' }}</td>
                        <td class="py-2">{{ $echance->reference ?: '-' }}</td>
                        <td class="py-2">
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                    <input
                                        type="radio"
                                        name="paid_status_{{ $echance->id }}"
                                        wire:click="setPaymentStatus({{ $echance->id }}, true)"
                                        @checked($echance->is_paid)
                                    />
                                    Paye
                                </label>
                                <label class="inline-flex items-center gap-1 text-xs text-slate-700">
                                    <input
                                        type="radio"
                                        name="paid_status_{{ $echance->id }}"
                                        wire:click="setPaymentStatus({{ $echance->id }}, false)"
                                        @checked(! $echance->is_paid)
                                    />
                                    Non paye
                                </label>
                            </div>
                            @if($echance->is_paid)
                                <select
                                    wire:change="setPaymentMethod({{ $echance->id }}, $event.target.value)"
                                    class="mt-2 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs"
                                >
                                    @foreach($this->paymentMethodOptions() as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" @selected(($echance->payment_method ?? 'cash') === $methodValue)>{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                        <td class="py-2 text-right font-semibold {{ $this->runningBalanceColorClass((float) $echance->running_balance) }}">{{ $fmt($echance->running_balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-3 text-slate-500">Aucune echance creee.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
