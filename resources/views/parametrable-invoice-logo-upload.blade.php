<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Invoice Logo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 py-8">
    <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold">Invoice Logo Uploader</h1>
        <p class="mt-1 text-sm text-slate-600">Invoice {{ $invoice->invoice_number ?: ('#' . $invoice->id) }}</p>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 rounded-lg border border-slate-200 p-4">
            <p class="mb-2 text-sm font-semibold">Current logo</p>
            @if ($invoice->print_logo)
                <img src="{{ asset('storage/' . ltrim($invoice->print_logo, '/')) }}" alt="Current logo" class="h-20 w-20 rounded object-cover" />
            @else
                <p class="text-sm text-slate-500">No custom logo set.</p>
            @endif
        </div>

        <form class="mt-5" method="POST" action="{{ route('parametrable-invoices.logo.update', ['parametrableInvoice' => $invoice]) }}" enctype="multipart/form-data">
            @csrf
            <label class="mb-2 block text-sm font-medium">Upload new logo (PNG/JPG/WEBP, max 5MB)</label>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required />
            <button type="submit" class="mt-4 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Upload logo</button>
        </form>

        @if ($invoice->print_logo)
            <form class="mt-3" method="POST" action="{{ route('parametrable-invoices.logo.destroy', ['parametrableInvoice' => $invoice]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Remove custom logo</button>
            </form>
        @endif

        <div class="mt-6">
            <a href="{{ \App\Filament\Company\Resources\Sales\ParametrableInvoiceResource::getUrl('edit', ['tenant' => auth()->user()?->currentCompany, 'record' => $invoice]) }}" class="text-sm font-semibold text-blue-600">Back to invoice edit</a>
        </div>
    </div>
</body>
</html>
