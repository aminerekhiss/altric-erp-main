<?php

namespace App\Filament\Company\Resources\Inventory\TicketResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Inventory\TicketResource;
use App\Models\Common\Product;
use App\Models\Common\Ticket;
use App\Services\TicketStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTicket extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data = $this->prepareProductData($data);

            /** @var Ticket $ticket */
            $ticket = parent::handleRecordCreation($data);

            if ($ticket->isValidated()) {
                app(TicketStockService::class)->apply($ticket, 'create-apply');
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
