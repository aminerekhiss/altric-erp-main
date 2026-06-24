<?php

namespace App\Filament\Company\Resources\Inventory\TicketResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Inventory\TicketResource;
use App\Models\Common\Product;
use App\Models\Common\Ticket;
use App\Services\TicketStockService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditTicket extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Ticket $record) {
                    if ($record->isValidated()) {
                        app(TicketStockService::class)->revert($record, 'delete-revert');
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['product_mode'] = filled($data['new_product_name'] ?? null) ? 'new' : 'select';

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $data = $this->prepareProductData($data);

            /** @var Ticket $record */
            $originalTicket = clone $record;

            if ($originalTicket->isValidated()) {
                app(TicketStockService::class)->revert($originalTicket, 'edit-revert');
            }

            $ticket = parent::handleRecordUpdate($record, $data);

            if ($ticket->isValidated()) {
                app(TicketStockService::class)->apply($ticket, 'edit-apply');
            }

            return $ticket;
        });
    }

    protected function prepareProductData(array $data): array
    {
        $companyId = auth()->user()?->current_company_id;
        $mode = $data['product_mode'] ?? 'select';

        if ($mode === 'new') {
            $newProductName = trim((string) ($data['new_product_name'] ?? ''));

            if ($newProductName === '') {
                throw ValidationException::withMessages([
                    'data.new_product_name' => 'Please enter a new product name.',
                ]);
            }

            /** @var Product $product */
            $product = Product::firstOrCreate(
                [
                    'company_id' => auth()->user()->current_company_id,
                    'name' => $newProductName,
                ],
                [
                    'sku' => null,
                    'price' => 0,
                    'cost' => 0,
                    'description' => null,
                    'is_active' => true,
                ]
            );

            $data['product_id'] = $product->id;
        } else {
            $product = Product::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->find((int) ($data['product_id'] ?? 0));

            if (! $product) {
                throw ValidationException::withMessages([
                    'data.product_id' => 'Selected product is invalid for your company.',
                ]);
            }

            $data['product_id'] = (int) $product->id;
        }

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        unset($data['product_mode']);

        return $data;
    }
}
