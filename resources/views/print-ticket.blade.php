<!DOCTYPE html>
<html>
<head>
    <title>Ticket #{{ $ticket->id }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
        }

        @media print {
            body {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }

            @page {
                size: auto;
                margin: 8mm;
            }
        }
    </style>
</head>
<body class="py-8">
<div class="mx-auto w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mb-8 flex items-start justify-between border-b pb-6">
        <div>
            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Stock Ticket</p>
            <h1 class="mt-2 text-3xl font-black uppercase tracking-wide">{{ $ticket->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">#{{ $ticket->id }} | {{ $ticket->date?->format('Y-m-d') }}</p>
        </div>

        <div class="w-28 text-right">
            @if ($ticket->logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->logo) }}" alt="Ticket logo" class="ml-auto h-16 w-16 rounded-lg object-cover" />
            @elseif (optional($ticket->company->profile)->logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->company->profile->logo) }}" alt="Company logo" class="ml-auto h-16 w-16 rounded-lg object-cover" />
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6 text-sm">
        <div class="space-y-2">
            <p><span class="font-semibold">Type:</span> {{ \App\Models\Common\Ticket::getTypeOptions()[$ticket->type] ?? ucfirst($ticket->type) }}</p>
            <p><span class="font-semibold">Status:</span> {{ ucfirst($ticket->status) }}</p>
            <p><span class="font-semibold">Provider:</span> {{ $ticket->provider ?: '-' }}</p>
        </div>
        <div class="space-y-2">
            <p><span class="font-semibold">Product:</span> {{ $ticket->product?->name ?: '-' }}</p>
            <p><span class="font-semibold">Quantity:</span> {{ $ticket->quantity }}</p>
            <p><span class="font-semibold">Company:</span> {{ $ticket->company?->name ?: '-' }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-slate-200 p-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-slate-500">Notes</p>
        <p class="text-sm text-slate-700">{{ $ticket->notes ?: 'No notes provided.' }}</p>
    </div>

    <div class="mt-10 flex items-end justify-between text-xs text-slate-500">
        <p>Generated at {{ now()->format('Y-m-d H:i') }}</p>
        <button onclick="window.print()" class="rounded-md bg-slate-900 px-4 py-2 text-white print:hidden">Print</button>
    </div>
</div>
</body>
</html>
