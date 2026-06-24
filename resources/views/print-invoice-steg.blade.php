<!DOCTYPE html>
<html>
<head>
    <title>Facture {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8fafc; color: #111827; }
        .print-sheet { background: #fff; border: 1px solid #d1d5db; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #6b7280; }
        @media print {
            body {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;
                margin: 0;
                padding: 0;
                background: #fff;
            }
            @page { size: auto; margin: 8mm; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body class="py-6">
<div class="mx-auto w-full max-w-5xl p-6 print-sheet">
    <div class="flex items-start justify-between">
        <div class="flex items-start gap-4 text-[12px] leading-4">
            <img src="{{ asset('logo.jfif') }}" alt="Logo" class="h-16 w-auto object-contain" />
            <div>
                <p>Route Manzel chaker km 3</p>
                <p>Tel.: 31 404 618/21 72027</p>
                <p>Direction Email</p>
                <p>M.F. 1626661 S/A/M/000</p>
            </div>
        </div>
        <div class="text-right text-[12px]">
            <p>{{ $invoice->invoice_city ?: 'Sfax' }}, le {{ $invoice->date?->format('d/m/Y') ?: company_today()->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="mt-4 flex justify-center">
        <div class="w-72 border border-gray-600 p-3 text-center text-[12px]">
            <p class="font-semibold">{{ $invoice->client_name ?: 'STEG ville' }}</p>
            <p class="mt-1"><span class="font-semibold">ADRESSE :</span> {{ $invoice->client_address ?: 'steg ville' }}</p>
        </div>
    </div>

    <div class="mt-6 border border-gray-600 text-center text-[30px] leading-none"></div>
    <h1 class="my-2 text-center text-2xl font-semibold">Facture N°{{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</h1>
    <div class="border border-gray-600 text-center text-[30px] leading-none"></div>

    <div class="mt-8 text-[13px]">
        <p><span class="font-semibold">OBJET:</span> {{ $invoice->object ?: '-' }}</p>
        <p class="mt-1"><span class="font-semibold">Bon de commande n°:</span> {{ $invoice->bon_de_commande ?: '-' }}</p>
    </div>

    <div class="mt-3 overflow-x-auto">
        <table class="text-[12px]">
            <thead>
            <tr class="bg-gray-100 text-center font-semibold">
                <th class="px-2 py-1">CODE</th>
                <th class="px-2 py-1">DESIGNATION</th>
                <th class="px-2 py-1">UNITE</th>
                <th class="px-2 py-1">QTE</th>
                <th class="px-2 py-1">P. U. H.T.</th>
                <th class="px-2 py-1">P. T. H.T.</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invoice->lines as $line)
                <tr>
                    <td class="px-2 py-1 text-center">{{ $line->code ?: '-' }}</td>
                    <td class="px-2 py-1">{{ $line->designation }}</td>
                    <td class="px-2 py-1 text-center">{{ $line->unit ?: '-' }}</td>
                    <td class="px-2 py-1 text-right">{{ number_format((float) $line->quantity, 3, '.', ' ') }}</td>
                    <td class="px-2 py-1 text-right">{{ number_format((float) $line->puht, 3, '.', ' ') }}</td>
                    <td class="px-2 py-1 text-right">{{ number_format((float) $line->ptht, 3, '.', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-2 py-3 text-center">Aucune ligne.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 ml-auto w-full max-w-md text-[13px]">
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">TOTAL H.T.:</span>
            <span>{{ number_format((float) ($computed['total_ht'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">T.V.A. 19%:</span>
            <span>{{ number_format((float) ($computed['tva_19'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">RG 5%:</span>
            <span>{{ number_format((float) ($computed['rg_5'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">TOTAL T.T.C.:</span>
            <span>{{ number_format((float) ($computed['total_ttc'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">RETENUE A LA SOURCE 1%:</span>
            <span>{{ number_format((float) ($computed['retenue_source_1'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 border-b-0 px-3 py-1">
            <span class="font-semibold">25% DE LA T.V.A.:</span>
            <span>{{ number_format((float) ($computed['tva_25'] ?? 0), 3, '.', ' ') }}</span>
        </div>
        <div class="flex justify-between border border-gray-600 px-3 py-2 text-[14px] font-bold">
            <span>NET A PAYER:</span>
            <span>{{ number_format((float) ($computed['net_a_payer'] ?? 0), 3, '.', ' ') }} {{ $invoice->currency_code }}</span>
        </div>
    </div>

    <p class="mt-5 text-[13px]">Arrete la presente facture a la somme de : {{ $computed['amount_in_words'] ?? '-' }}.</p>

    @if($invoice->notes)
        <div class="mt-4 border border-gray-400 p-3 text-[12px]">
            <p class="font-semibold">Notes</p>
            <p class="mt-1">{{ $invoice->notes }}</p>
        </div>
    @endif

    <div class="mt-8 text-right">
        <button onclick="window.print()" class="print-btn rounded bg-gray-900 px-4 py-2 text-sm text-white">Print</button>
    </div>
</div>
</body>
</html>
