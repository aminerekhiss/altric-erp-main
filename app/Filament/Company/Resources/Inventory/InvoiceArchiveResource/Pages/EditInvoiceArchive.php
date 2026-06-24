<?php

namespace App\Filament\Company\Resources\Inventory\InvoiceArchiveResource\Pages;

use App\Filament\Company\Resources\Inventory\InvoiceArchiveResource;
use App\Filament\Company\Resources\Inventory\TicketResource\Pages\ViewTicket;
use App\Models\Accounting\FinanceEchance;
use App\Models\Common\Client;
use App\Models\Common\Product;
use App\Models\Common\Ticket;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditInvoiceArchive extends EditRecord
{
    protected static string $resource = InvoiceArchiveResource::class;

    public function getTitle(): string
    {
        return translate('Edit Invoice Archive');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openInvoice')
                ->label(translate('Open invoice file'))
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->visible(fn (): bool => filled($this->record?->invoice_file))
                ->url(fn (): ?string => $this->record?->invoice_file ? asset('storage/' . ltrim((string) $this->record->invoice_file, '/')) : null)
                ->openUrlInNewTab(),
            Actions\Action::make('viewTicket')
                ->label(translate('View ticket'))
                ->icon('heroicon-m-eye')
                ->url(fn (): string => ViewTicket::getUrl(['record' => $this->record])),
            Actions\DeleteAction::make()
                ->visible(false),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return translate('Invoice archive updated');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $companyId = auth()->user()?->current_company_id;

        $product = Product::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->find((int) ($data['product_id'] ?? 0));

        if (! $product) {
            throw ValidationException::withMessages([
                'data.product_id' => translate('Selected product is invalid for your company.'),
            ]);
        }

        $data['product_id'] = (int) $product->id;

        if (filled($data['client_id'] ?? null)) {
            $client = Client::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->find((int) $data['client_id']);

            if (! $client) {
                throw ValidationException::withMessages([
                    'data.client_id' => translate('Selected client is invalid for your company.'),
                ]);
            }

            $data['client_id'] = (int) $client->id;
        } else {
            $data['client_id'] = null;
        }

        $data['invoice_folder'] = $data['invoice_folder'] ?? 'general';
        $data['date'] = $data['date'] ?? ($data['invoice_date'] ?? now()->toDateString());

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Ticket $ticket */
        $ticket = $this->record;

        $amount = (float) ($ticket->invoice_amount ?? 0);

        if ($amount <= 0) {
            FinanceEchance::query()
                ->where('company_id', (int) $ticket->company_id)
                ->where('source_type', 'invoice_archive')
                ->where('source_id', (int) $ticket->id)
                ->delete();

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
            ]
        );
    }
}
