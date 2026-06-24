<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8fafc; color: #0f172a; }
        @media print {
            body {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            @page { size: auto; margin: 8mm; }
        }
    </style>
</head>
<body class="py-8">
@php
    $printLogo = trim((string) ($invoice->print_logo ?? ''));
    $companyLogo = trim((string) (optional($invoice->company->profile)->logo ?? ''));
@endphp
<div class="mx-auto w-full max-w-5xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-6 flex items-start justify-between border-b pb-5">
        <div>
            @if($invoice->print_header)
                <p class="mb-2 whitespace-pre-line text-sm text-slate-700">{{ $invoice->print_header }}</p>
            @endif
            <h1 class="mt-2 text-2xl font-bold">Facture {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</h1>
            <p class="text-sm text-slate-600">Client: {{ $invoice->client_name ?: '-' }}</p>
            <p class="text-sm text-slate-600">Objet: {{ $invoice->object ?: '-' }}</p>
        </div>
        <div class="w-44 text-right">
            <p class="mb-2 text-sm text-slate-600">Date: {{ $invoice->date?->format('Y-m-d') ?: '-' }}</p>
            @if ($printLogo !== '')
                <img src="{{ asset('storage/' . ltrim($printLogo, '/')) }}" alt="Invoice logo" class="ml-auto h-16 w-16 rounded-lg object-cover" />
            @elseif ($companyLogo !== '')
                <img src="{{ asset('storage/' . ltrim($companyLogo, '/')) }}" alt="Company logo" class="ml-auto h-16 w-16 rounded-lg object-cover" />
            @else
                <div class="ml-auto h-16 w-16 rounded-lg border border-dashed border-slate-300"></div>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
            <tr class="bg-slate-100 text-left">
                <th class="border border-slate-300 px-2 py-2">Designation</th>
                <th class="border border-slate-300 px-2 py-2">Unite</th>
                <th class="border border-slate-300 px-2 py-2 text-right">QTE</th>
                <th class="border border-slate-300 px-2 py-2 text-right">P.U.H.T</th>
                <th class="border border-slate-300 px-2 py-2 text-right">P.T.H.T</th>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td class="border border-slate-300 px-2 py-2">{{ $line->designation }}</td>
                    <td class="border border-slate-300 px-2 py-2">{{ $line->unit ?: '-' }}</td>
                    <td class="border border-slate-300 px-2 py-2 text-right">{{ number_format((float) $line->quantity, 3, '.', ' ') }}</td>
                    <td class="border border-slate-300 px-2 py-2 text-right">{{ number_format((float) $line->puht, 3, '.', ' ') }}</td>
                    <td class="border border-slate-300 px-2 py-2 text-right">{{ number_format((float) $line->ptht, 3, '.', ' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 ml-auto w-full max-w-md text-sm">
        <div class="flex justify-between border-b py-1">
            <span class="font-semibold">TOTAL H.T</span>
            <span>{{ number_format((float) ($computed['total_ht'] ?? 0), 3, '.', ' ') }}</span>
        </div>

        @foreach($printAdjustments as $adjustment)
            <div class="flex justify-between border-b py-1">
                <span>{{ $adjustment['label'] }} ({{ rtrim(rtrim(number_format((float) $adjustment['percentage'], 3, '.', ''), '0'), '.') }}%)</span>
                <span>{{ $adjustment['operation'] === 'subtract' ? '-' : '+' }}{{ number_format((float) $adjustment['amount'], 3, '.', ' ') }}</span>
            </div>
        @endforeach

        <div class="flex justify-between border-b py-1">
            <span class="font-semibold">Adjustments total</span>
            <span>{{ number_format((float) ($computed['adjustments_total'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between py-2 text-base font-bold">
            <span>NET A PAYER</span>
            <span>{{ number_format((float) ($computed['net_ht'] ?? 0), 3, '.', ' ') }} {{ $invoice->currency_code }}</span>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-slate-200 p-3 text-sm">
        <p>{{ $computed['amount_in_words'] ?? ($invoice->amount_in_words ?: '-') }}</p>
    </div>

    @if($invoice->notes)
        <div class="mt-4 rounded-lg border border-slate-200 p-3 text-sm">
            <p class="font-semibold">Notes</p>
            <p class="mt-1">{{ $invoice->notes }}</p>
        </div>
    @endif

    @if($invoice->print_footer)
        <div class="mt-4 rounded-lg border border-slate-200 p-3 text-sm whitespace-pre-line">
            {{ $invoice->print_footer }}
        </div>
    @endif

    <div class="mt-8 flex items-end justify-end text-xs text-slate-500">
        <button onclick="window.print()" class="rounded-md bg-slate-900 px-4 py-2 text-white print:hidden">Print</button>
    </div>
</div>
</body>
</html>
