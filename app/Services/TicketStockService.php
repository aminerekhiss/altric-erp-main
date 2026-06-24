<?php

namespace App\Services;

use App\Models\Common\Stock;
use App\Models\Common\StockMovement;
use App\Models\Common\Ticket;
use Illuminate\Validation\ValidationException;

class TicketStockService
{
    public function apply(Ticket $ticket, string $operation = 'apply'): void
    {
        if ($ticket->type === Ticket::TYPE_ENTRANCE) {
            $this->increaseStock($ticket, $operation);

            return;
        }

        $this->decreaseStock($ticket, $operation);
    }

    public function revert(Ticket $ticket, string $operation = 'revert'): void
    {
        if ($ticket->type === Ticket::TYPE_ENTRANCE) {
            $this->decreaseStock($ticket, $operation);

            return;
        }

        $this->increaseStock($ticket, $operation);
    }

    protected function increaseStock(Ticket $ticket, string $operation): void
    {
        $stock = Stock::query()
            ->where('company_id', $ticket->company_id)
            ->where('product_id', $ticket->product_id)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $beforeQuantity = 0;

            Stock::create([
                'company_id' => $ticket->company_id,
                'product_id' => $ticket->product_id,
                'quantity' => $ticket->quantity,
                'location' => 'Main Stock',
                'notes' => 'Created from ' . ucfirst($ticket->type) . ' ticket #' . $ticket->id,
            ]);

            $stock = Stock::query()
                ->where('company_id', $ticket->company_id)
                ->where('product_id', $ticket->product_id)
                ->orderByDesc('id')
                ->first();

            if ($stock) {
                $this->logMovement(
                    ticket: $ticket,
                    stock: $stock,
                    direction: 'in',
                    operation: $operation,
                    quantity: $ticket->quantity,
                    beforeQuantity: $beforeQuantity,
                    afterQuantity: (int) $stock->quantity
                );
            }

            return;
        }

        $beforeQuantity = (int) $stock->quantity;
        $stock->increment('quantity', $ticket->quantity);
        $stock->refresh();

        $this->logMovement(
            ticket: $ticket,
            stock: $stock,
            direction: 'in',
            operation: $operation,
            quantity: $ticket->quantity,
            beforeQuantity: $beforeQuantity,
            afterQuantity: (int) $stock->quantity
        );
    }

    protected function decreaseStock(Ticket $ticket, string $operation): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Stock> $stocks */
        $stocks = Stock::query()
            ->where('company_id', $ticket->company_id)
            ->where('product_id', $ticket->product_id)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        $availableQuantity = (int) $stocks->sum('quantity');

        if ($availableQuantity < $ticket->quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Exit quantity exceeds available stock (' . $availableQuantity . ').',
            ]);
        }

        $remainingToDeduct = $ticket->quantity;

        foreach ($stocks as $stock) {
            /** @var Stock $stock */
            if ($remainingToDeduct <= 0) {
                break;
            }

            $beforeQuantity = (int) $stock->quantity;
            $deducted = min($stock->quantity, $remainingToDeduct);
            $stock->decrement('quantity', $deducted);
            $stock->refresh();
            $remainingToDeduct -= $deducted;

            $this->logMovement(
                ticket: $ticket,
                stock: $stock,
                direction: 'out',
                operation: $operation,
                quantity: (int) $deducted,
                beforeQuantity: $beforeQuantity,
                afterQuantity: (int) $stock->quantity
            );
        }
    }

    protected function logMovement(
        Ticket $ticket,
        Stock $stock,
        string $direction,
        string $operation,
        int $quantity,
        int $beforeQuantity,
        int $afterQuantity,
    ): void {
        StockMovement::create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'product_id' => $ticket->product_id,
            'stock_id' => $stock->id,
            'direction' => $direction,
            'operation' => $operation,
            'quantity' => $quantity,
            'before_quantity' => $beforeQuantity,
            'after_quantity' => $afterQuantity,
            'notes' => ucfirst($operation) . ' from ' . ucfirst($ticket->type) . ' ticket #' . $ticket->id,
        ]);
    }
}
