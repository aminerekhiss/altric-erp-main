<?php

namespace App\Filament\Company\Resources\Inventory\StockResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Inventory\StockResource;
use App\Models\Common\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditStock extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = StockResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
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

        if ($companyId) {
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
