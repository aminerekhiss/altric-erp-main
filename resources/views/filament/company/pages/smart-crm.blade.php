<x-filament-panels::page>
    @php
        $fmt = static fn (float|int $value): string => number_format((float) $value, 2, '.', ' ');
        $money = static fn (float|int $value): string => number_format((float) $value, 3, '.', ' ') . ' TND';
        $chartPayload = [
            'churn' => [
                'labels' => $insights['churn_bucket_labels'],
                'values' => $insights['churn_bucket_values'],
            ],
            'activity' => [
                'labels' => $insights['activity_bucket_labels'],
                'values' => $insights['activity_bucket_values'],
            ],
            'topClients' => [
                'labels' => collect($insights['top_clients'])->pluck('name')->all(),
                'churn' => collect($insights['top_clients'])->pluck('churn_percent')->all(),
                'activity' => collect($insights['top_clients'])->pluck('activity_score')->all(),
            ],
        ];
    @endphp

    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="absolute -top-10 right-0 h-28 w-28 rounded-full bg-sky-200/50 blur-2xl"></div>
        <div class="absolute -bottom-10 left-0 h-28 w-28 rounded-full bg-emerald-200/50 blur-2xl"></div>
        <div class="relative">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Smart CRM</h2>
            <p class="mt-1 text-sm text-slate-600">Prediction du churn client, score d'activite et pilotage intelligent des leads.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Churn moyen</p>
            <p class="mt-1 text-3xl font-bold text-rose-900">{{ $fmt($insights['avg_churn_percent']) }}%</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Score activite moyen</p>
            <p class="mt-1 text-3xl font-bold text-emerald-900">{{ $fmt($insights['avg_activity_score']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Clients a risque</p>
            <p class="mt-1 text-3xl font-bold text-amber-900">{{ number_format($insights['at_risk_count']) }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Total clients analyses</p>
            <p class="mt-1 text-3xl font-bold text-sky-900">{{ number_format($insights['clients_count']) }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Distribution Churn %</h3>
            <div class="mt-3 w-full" style="height: min(60vh, 580px);">
                <canvas id="crmChurnChart" class="h-full w-full"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Distribution Activity Score</h3>
            <div class="mt-3 w-full" style="height: min(60vh, 580px);">
                <canvas id="crmActivityChart" class="h-full w-full"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm xl:col-span-2">
            <h3 class="text-base font-semibold text-slate-900">Top clients: Churn vs Activity</h3>
            <div class="mt-3 w-full" style="height: min(72vh, 760px);">
                <canvas id="crmTopClientsChart" class="h-full w-full"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Create Lead</h3>
            <p class="mt-1 text-sm text-slate-500">Ajoutez un lead, liez-le a un client existant et suivez la conversion.</p>

            <x-filament-panels::form wire:submit="createLead" class="mt-3">
                {{ $this->form }}

                <div class="mt-4 flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-plus">
                        Create Lead
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Latest Leads</h3>
            <div class="mt-3 overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-2">Lead</th>
                        <th class="pb-2">Source</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2 text-right">Expected</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($leads as $lead)
                        <tr class="border-t border-slate-100">
                            <td class="py-2">
                                <div class="font-medium text-slate-900">{{ $lead->name }}</div>
                                <div class="text-xs text-slate-500">{{ $lead->email }}</div>
                            </td>
                            <td class="py-2">{{ ucfirst((string) $lead->source) }}</td>
                            <td class="py-2">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ ucfirst((string) $lead->status) }}</span>
                            </td>
                            <td class="py-2 text-right">{{ is_null($lead->expected_value) ? '-' : $money((float) $lead->expected_value) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-3 text-slate-500">No leads yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900">AI CRM Insights</h3>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Churn Intelligence</span>
        </div>
        @if(filled($aiInsights))
            <div class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ $aiInsights }}</div>
        @else
            <p class="mt-2 text-sm text-slate-500">Use AI Churn Insights action to generate recommendations.</p>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const payload = @json($chartPayload);
            window.smartCrmCharts = window.smartCrmCharts || {};

            const destroyChart = (id) => {
                if (window.smartCrmCharts[id]) {
                    window.smartCrmCharts[id].destroy();
                    delete window.smartCrmCharts[id];
                }
            };

            const churnCanvas = document.getElementById('crmChurnChart');
            if (churnCanvas) {
                destroyChart('crmChurnChart');
                window.smartCrmCharts.crmChurnChart = new Chart(churnCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: payload.churn.labels,
                        datasets: [{
                            data: payload.churn.values,
                            backgroundColor: ['#86efac', '#fde68a', '#fca5a5', '#ef4444'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }

            const activityCanvas = document.getElementById('crmActivityChart');
            if (activityCanvas) {
                destroyChart('crmActivityChart');
                window.smartCrmCharts.crmActivityChart = new Chart(activityCanvas, {
                    type: 'bar',
                    data: {
                        labels: payload.activity.labels,
                        datasets: [{
                            label: 'Clients',
                            data: payload.activity.values,
                            backgroundColor: '#0ea5e9',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,0.25)' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }

            const topClientsCanvas = document.getElementById('crmTopClientsChart');
            if (topClientsCanvas) {
                destroyChart('crmTopClientsChart');
                window.smartCrmCharts.crmTopClientsChart = new Chart(topClientsCanvas, {
                    type: 'bar',
                    data: {
                        labels: payload.topClients.labels,
                        datasets: [
                            {
                                label: 'Churn %',
                                data: payload.topClients.churn,
                                backgroundColor: 'rgba(239,68,68,0.75)',
                                borderRadius: 6,
                            },
                            {
                                label: 'Activity Score',
                                data: payload.topClients.activity,
                                backgroundColor: 'rgba(16,185,129,0.75)',
                                borderRadius: 6,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: { maxTicksLimit: 8 },
                                grid: { color: 'rgba(148,163,184,0.25)' },
                            },
                            x: {
                                ticks: { autoSkip: true, maxTicksLimit: 10 },
                                grid: { display: false },
                            },
                        },
                    },
                });
            }
        })();
    </script>
</x-filament-panels::page>
