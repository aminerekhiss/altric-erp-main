<?php

namespace App\Observers;

use App\Enums\Accounting\BillStatus;
use App\Models\Accounting\Bill;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\FinanceEchance;
use App\Models\Accounting\Transaction;
use Illuminate\Support\Facades\DB;

class BillObserver
{
    public function created(Bill $bill): void
    {
        $this->syncEchanceSortie($bill);

        // $bill->createInitialTransaction();
    }

    public function updated(Bill $bill): void
    {
        $this->syncEchanceSortie($bill);
    }

    public function saving(Bill $bill): void
    {
        if ($bill->isDirty('due_date') && $bill->status === BillStatus::Overdue && ! $bill->shouldBeOverdue() && ! $bill->hasPayments()) {
            $bill->status = BillStatus::Open;

            return;
        }

        if ($bill->shouldBeOverdue()) {
            $bill->status = BillStatus::Overdue;
        }
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleted(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            FinanceEchance::query()
                ->where('source_type', 'bill')
                ->where('source_id', $bill->id)
                ->delete();

            $bill->lineItems()->each(function (DocumentLineItem $lineItem) {
                $lineItem->delete();
            });

            $bill->transactions()->each(function (Transaction $transaction) {
                $transaction->delete();
            });
        });
    }

    private function syncEchanceSortie(Bill $bill): void
    {
        $supplierName = $bill->vendor?->name;

        FinanceEchance::query()->updateOrCreate(
            [
                'company_id' => $bill->company_id,
                'source_type' => 'bill',
                'source_id' => $bill->id,
            ],
            [
                'reference' => (string) ($bill->bill_number ?: ('BILL-' . $bill->id)),
                'entry_type' => FinanceEchance::ENTRY_SORTIE,
                'amount' => (float) ($bill->total ?? 0),
                'echance_date' => optional($bill->due_date ?? $bill->date)->toDateString() ?? company_today()->toDateString(),
                'supplier' => $supplierName,
                'is_paid' => $bill->status === BillStatus::Paid,
            ]
        );
    }
}
