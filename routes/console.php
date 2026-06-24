<?php

use App\Console\Commands\TriggerRecurringInvoiceGeneration;
use App\Console\Commands\UpdateOverdueInvoices;
use App\Console\Commands\SendCarDueReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command(UpdateOverdueInvoices::class)->everyFiveMinutes();
Schedule::command(TriggerRecurringInvoiceGeneration::class, ['--queue'])->everyMinute();
Schedule::command(SendCarDueReminders::class)->everyThirtyMinutes();
