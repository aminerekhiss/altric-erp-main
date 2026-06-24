<?php

namespace App\Console\Commands;

use App\Models\Common\Car;
use App\Models\Company;
use App\Models\Notification;
use App\Notifications\CarDueReminderNotification;
use Illuminate\Console\Command;

class SendCarDueReminders extends Command
{
    protected $signature = 'cars:send-due-reminders';

    protected $description = 'Send owner notifications when car assurance/vignette/visite dates are due';

    public function handle(): int
    {
        $today = now()->toDateString();

        $companies = Company::query()
            ->with('owner')
            ->get();

        foreach ($companies as $company) {
            $owner = $company->owner;

            if (! $owner) {
                continue;
            }

            session(['current_company_id' => $company->id]);

            $cars = Car::query()
                ->where('company_id', $company->id)
                ->where(function ($query) use ($today) {
                    $query
                        ->whereDate('assurance_date', $today)
                        ->orWhereDate('vignette_date', $today)
                        ->orWhereDate('visite_date', $today);
                })
                ->get();

            foreach ($cars as $car) {
                $dueItems = [
                    'assurance' => [
                        'date' => optional($car->assurance_date)->toDateString(),
                        'amount' => $car->assurance_amount,
                    ],
                    'vignette' => [
                        'date' => optional($car->vignette_date)->toDateString(),
                        'amount' => $car->vignette_amount,
                    ],
                    'visite' => [
                        'date' => optional($car->visite_date)->toDateString(),
                        'amount' => $car->visite_amount,
                    ],
                ];

                foreach ($dueItems as $type => $item) {
                    if (($item['date'] ?? null) !== $today) {
                        continue;
                    }

                    $reminderKey = sprintf('car:%d:%s:%s', $car->id, $type, $today);

                    $alreadySent = Notification::query()
                        ->where('company_id', $company->id)
                        ->where('notifiable_type', get_class($owner))
                        ->where('notifiable_id', $owner->id)
                        ->where('data->reminder_key', $reminderKey)
                        ->whereDate('created_at', $today)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $url = route('filament.company.resources.sales.cars.edit', [
                        'tenant' => $company,
                        'record' => $car,
                    ]);

                    $owner->notify(new CarDueReminderNotification(
                        car: $car,
                        type: $type,
                        dueDate: $today,
                        amount: is_null($item['amount']) ? null : (float) $item['amount'],
                        url: $url,
                        reminderKey: $reminderKey,
                    ));
                }
            }
        }

        $this->info('Car due reminders processed successfully.');

        return self::SUCCESS;
    }
}
