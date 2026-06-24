<?php

use App\Http\Controllers\DocumentPrintController;
use App\Http\Controllers\EmployeeSalaryPrintController;
use App\Http\Controllers\InvoiceStegPrintController;
use App\Http\Controllers\ParametrableInvoiceLogoController;
use App\Http\Controllers\ParametrableInvoicePrintController;
use App\Http\Controllers\TicketPrintController;
use App\Http\Middleware\AllowSameOriginFrame;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(Filament::getDefaultPanel()->getUrl());
});

Route::middleware(['auth'])->group(function () {
    Route::get('documents/{documentType}/{id}/print', [DocumentPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.print');

    Route::get('tickets/{ticket}/print', [TicketPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('tickets.print');

    Route::get('employee-salaries/{employeeSalary}/print', [EmployeeSalaryPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('employee-salaries.print');

    Route::get('parametrable-invoices/{parametrableInvoice}/print', [ParametrableInvoicePrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('parametrable-invoices.print');

    Route::get('invoice-stegs/{invoiceSteg}/print', [InvoiceStegPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('invoice-stegs.print');

    Route::get('parametrable-invoices/{parametrableInvoice}/logo', [ParametrableInvoiceLogoController::class, 'edit'])
        ->name('parametrable-invoices.logo.edit');

    Route::post('parametrable-invoices/{parametrableInvoice}/logo', [ParametrableInvoiceLogoController::class, 'update'])
        ->name('parametrable-invoices.logo.update');

    Route::delete('parametrable-invoices/{parametrableInvoice}/logo', [ParametrableInvoiceLogoController::class, 'destroy'])
        ->name('parametrable-invoices.logo.destroy');
});
