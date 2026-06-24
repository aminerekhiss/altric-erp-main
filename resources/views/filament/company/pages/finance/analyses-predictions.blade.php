<x-filament-panels::page>
    @php
        $money = static fn (float|int $value): string => number_format((float) $value, 3, '.', ' ') . ' TND';
        $chartPayload = [
            'sales' => $salesChart,
            'purchases' => $purchaseChart,
            'cashflow' => $cashflowChart,
        ];
    @endphp

    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-r from-cyan-50 via-white to-blue-50 p-6 shadow-sm">
        <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-cyan-200/40 blur-2xl"></div>
        <div class="absolute -bottom-16 -left-8 h-40 w-40 rounded-full bg-blue-200/40 blur-2xl"></div>
        <div class="relative">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Analyses et Predictions</h2>
            <p class="mt-1 text-sm text-slate-600">Tableau decisionnel base sur vos vraies donnees (ventes, achats, tresorerie) + projection 3 mois.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Ventes (mois)</p>
            <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $money($kpis['current_month_sales']) }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Achats (mois)</p>
            <p class="mt-1 text-2xl font-bold text-rose-900">{{ $money($kpis['current_month_purchases']) }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Cashflow net (mois)</p>
            <p class="mt-1 text-2xl font-bold text-sky-900">{{ $money($kpis['current_month_cashflow']) }}</p>
        </div>
        <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Clients actifs</p>
            <p class="mt-1 text-2xl font-bold text-violet-900">{{ number_format((int) $kpis['active_clients']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Taux encaissement 90j</p>
            <p class="mt-1 text-2xl font-bold text-amber-900">{{ number_format((float) $kpis['collection_rate'], 1, '.', ' ') }}%</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Tendance ventes (12 mois)</h3>
            <div class="mt-3 w-full" style="height: min(72vh, 760px);">
                <canvas id="salesChartCanvas" class="h-full w-full"></canvas>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Tendance achats (12 mois)</h3>
            <div class="mt-3 w-full" style="height: min(72vh, 760px);">
                <canvas id="purchaseChartCanvas" class="h-full w-full"></canvas>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm xl:col-span-2">
            <h3 class="text-base font-semibold text-slate-900">Cashflow net (12 mois)</h3>
            <div class="mt-3 w-full" style="height: min(80vh, 860px);">
                <canvas id="cashflowChartCanvas" class="h-full w-full"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-emerald-200 bg-white p-4">
            <h4 class="text-sm font-semibold text-emerald-800">Prediction ventes (3 mois)</h4>
            <div class="mt-3 space-y-2">
                @foreach($salesPredictions as $item)
                    <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2">
                        <span class="text-sm font-medium text-emerald-900">{{ $item['label'] }}</span>
                        <span class="text-sm font-semibold text-emerald-900">{{ $money($item['value']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-white p-4">
            <h4 class="text-sm font-semibold text-rose-800">Prediction achats (3 mois)</h4>
            <div class="mt-3 space-y-2">
                @foreach($purchasePredictions as $item)
                    <div class="flex items-center justify-between rounded-lg bg-rose-50 px-3 py-2">
                        <span class="text-sm font-medium text-rose-900">{{ $item['label'] }}</span>
                        <span class="text-sm font-semibold text-rose-900">{{ $money($item['value']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-sky-200 bg-white p-4">
            <h4 class="text-sm font-semibold text-sky-800">Prediction cashflow (3 mois)</h4>
            <div class="mt-3 space-y-2">
                @foreach($cashflowPredictions as $item)
                    <div class="flex items-center justify-between rounded-lg bg-sky-50 px-3 py-2">
                        <span class="text-sm font-medium text-sky-900">{{ $item['label'] }}</span>
                        <span class="text-sm font-semibold text-sky-900">{{ $money($item['value']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900">Insights IA</h3>
            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800">Grok Analysis</span>
        </div>

        @if(filled($aiInsights))
            <div class="prose prose-sm mt-3 max-w-none text-slate-700 whitespace-pre-wrap">{{ $aiInsights }}</div>
        @else
            <p class="mt-2 text-sm text-slate-500">Cliquez sur Generer Insights IA pour obtenir des recommandations de pilotage (risques, actions, priorites).</p>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const payload = @json($chartPayload);

            window.analyticsCharts = window.analyticsCharts || {};

            const destroyExistingChart = (canvasId) => {
                if (window.analyticsCharts[canvasId]) {
                    window.analyticsCharts[canvasId].destroy();
                    delete window.analyticsCharts[canvasId];
                }
            };

            const createLineChart = (canvasId, label, color, dataset) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                destroyExistingChart(canvasId);

                const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 260);
                gradient.addColorStop(0, color + '55');
                gradient.addColorStop(1, color + '00');

                window.analyticsCharts[canvasId] = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: dataset.labels,
                        datasets: [{
                            label,
                            data: dataset.values,
                            borderColor: color,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString('fr-FR', {minimumFractionDigits: 3, maximumFractionDigits: 3})} TND`,
                                },
                            },
                        },
                        scales: {
                            y: {
                                ticks: {
                                    maxTicksLimit: 8,
                                    callback: (value) => `${Number(value).toLocaleString('fr-FR')} TND`,
                                },
                                grid: { color: 'rgba(148, 163, 184, 0.25)' },
                            },
                            x: {
                                ticks: {
                                    autoSkip: true,
                                    maxTicksLimit: 8,
                                },
                                grid: { display: false },
                            },
                        },
                    },
                });
            };

            createLineChart('salesChartCanvas', 'Ventes', '#059669', payload.sales);
            createLineChart('purchaseChartCanvas', 'Achats', '#e11d48', payload.purchases);
            createLineChart('cashflowChartCanvas', 'Cashflow net', '#0284c7', payload.cashflow);
        })();
    </script>
</x-filament-panels::page>
