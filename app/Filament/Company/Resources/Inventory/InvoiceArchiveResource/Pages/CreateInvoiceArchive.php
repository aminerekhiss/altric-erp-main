<?php

namespace App\Filament\Company\Resources\Inventory\InvoiceArchiveResource\Pages;

use App\Filament\Company\Resources\Inventory\InvoiceArchiveResource;
use App\Models\Accounting\FinanceEchance;
use App\Models\Common\Client;
use App\Models\Common\Product;
use App\Models\Common\Ticket;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInvoiceArchive extends CreateRecord
{
    protected static string $resource = InvoiceArchiveResource::class;

    public function getTitle(): string
    {
        return 'Upload Invoice Archive';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Invoice archive uploaded successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = auth()->user()?->current_company_id;

        $product = Product::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->find((int) ($data['product_id'] ?? 0));

        if (! $product) {
            throw ValidationException::withMessages([
                'data.product_id' => 'Selected product is invalid for your company.',
            ]);
        }

        $data['product_id'] = (int) $product->id;

        if (filled($data['client_id'] ?? null)) {
            $client = Client::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->find((int) $data['client_id']);

            if (! $client) {
                throw ValidationException::withMessages([
                    'data.client_id' => 'Selected client is invalid for your company.',
                ]);
            }

            $data['client_id'] = (int) $client->id;
        } else {
            $data['client_id'] = null;
        }

        $data['type'] = $data['type'] ?? Ticket::TYPE_ENTRANCE;
        $data['status'] = $data['status'] ?? Ticket::STATUS_VALIDATED;
        $data['invoice_folder'] = $data['invoice_folder'] ?? 'general';
        $data['date'] = $data['date'] ?? ($data['invoice_date'] ?? now()->toDateString());

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncEchanceSortie($this->record);
    }

    private function syncEchanceSortie(Ticket $ticket): void
    {
        $amount = (float) ($ticket->invoice_amount ?? 0);

        if ($amount <= 0) {
            return;
        }

        FinanceEchance::query()->updateOrCreate(
            [
                'company_id' => (int) $ticket->company_id,
                'source_type' => 'invoice_archive',
                'source_id' => (int) $ticket->id,
            ],
            [
                'reference' => (string) ($ticket->name ?: ('ARCH-' . $ticket->id)),
                'entry_type' => FinanceEchance::ENTRY_SORTIE,
                'amount' => $amount,
                'echance_date' => optional($ticket->invoice_date)->toDateString() ?? company_today()->toDateString(),
                'supplier' => $ticket->invoice_from,
                'is_paid' => false,
            ]
        );
    }
}
