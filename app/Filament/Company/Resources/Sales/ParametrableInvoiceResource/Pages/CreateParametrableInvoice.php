<?php

namespace App\Filament\Company\Resources\Sales\ParametrableInvoiceResource\Pages;

use App\Filament\Company\Resources\Sales\ParametrableInvoiceResource;
use App\Models\Common\ParametrableInvoice;
use App\Support\ParametrableInvoiceCalculator;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateParametrableInvoice extends CreateRecord
{
    protected static string $resource = ParametrableInvoiceResource::class;

    #[Url(as: 'structure')]
    public ?int $structureId = null;

    public function mount(): void
    {
        parent::mount();

        if (! $this->structureId) {
            return;
        }

        $structure = ParametrableInvoice::query()
            ->with(['lines', 'adjustments'])
            ->where('is_structure', true)
            ->find($this->structureId);

        if (! $structure) {
            return;
        }

        $this->form->fill([
            'invoice_number' => null,
            'client_name' => null,
            'object' => $structure->object,
            'date' => company_today()->toDateString(),
            'currency_code' => $structure->currency_code,
            'is_structure' => false,
            'structure_name' => null,
            'notes' => $structure->notes,
            'print_logo' => $structure->print_logo,
            'print_header' => $structure->print_header,
            'print_footer' => $structure->print_footer,
            'lines' => $structure->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'designation' => $line->designation,
                    'unit' => $line->unit,
                    'quantity' => (float) $line->quantity,
                    'puht' => (float) $line->puht,
                    'ptht' => (float) $line->ptht,
                ];
            })->toArray(),
            'adjustments' => $structure->adjustments->map(function ($adjustment) {
                return [
                    'label' => $adjustment->label,
                    'operation' => $adjustment->operation,
                    'percentage' => (float) $adjustment->percentage,
                    'amount' => (float) $adjustment->amount,
                ];
            })->toArray(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['print_logo']) && $this->structureId) {
            $structure = ParametrableInvoice::query()
                ->where('is_structure', true)
                ->find($this->structureId);

            if ($structure?->print_logo) {
                $data['print_logo'] = $structure->print_logo;
            }
        }

        $totals = ParametrableInvoiceCalculator::calculate(
            $data['lines'] ?? [],
            $data['adjustments'] ?? []
        );

        $data['lines'] = $totals['lines'];
        $data['adjustments'] = $totals['adjustments'];
        $data['total_ht'] = $totals['total_ht'];
        $data['adjustments_total'] = $totals['adjustments_total'];
        $data['net_ht'] = $totals['net_ht'];
        $data['amount_in_words'] = $totals['amount_in_words'];

        return $data;
    }
}
