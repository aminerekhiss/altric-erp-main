<?php

namespace App\Notifications;

use App\Models\Common\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CarDueReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Car $car,
        protected string $type,
        protected string $dueDate,
        protected ?float $amount,
        protected string $url,
        protected string $reminderKey,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $typeLabel = match ($this->type) {
            'assurance' => 'Assurance',
            'vignette' => 'Vignette',
            'visite' => 'Visite',
            default => ucfirst($this->type),
        };

        $amountText = is_null($this->amount)
            ? 'Amount: not set'
            : 'Amount: ' . number_format($this->amount, 3, '.', ' ') . ' TND';

        return [
            'title' => 'Car ' . $typeLabel . ' due today',
            'body' => sprintf(
                '%s for car %s is due on %s. %s.',
                $typeLabel,
                $this->car->car_number,
                $this->dueDate,
                $amountText,
            ),
            'car_id' => $this->car->id,
            'car_number' => $this->car->car_number,
            'reminder_type' => $this->type,
            'due_date' => $this->dueDate,
            'amount' => $this->amount,
            'reminder_key' => $this->reminderKey,
            'url' => $this->url,
        ];
    }
}
