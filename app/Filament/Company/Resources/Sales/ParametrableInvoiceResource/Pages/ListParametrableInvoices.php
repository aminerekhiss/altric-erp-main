<?php

namespace App\Filament\Company\Resources\Sales\ParametrableInvoiceResource\Pages;

use App\Filament\Company\Resources\Sales\InvoiceStegResource;
use App\Filament\Company\Resources\Sales\ParametrableInvoiceResource;
use App\Models\Common\ParametrableInvoice;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListParametrableInvoices extends ListRecords
{
    protected static string $resource = ParametrableInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('invoiceSteg')
                ->label('Invoice STEG')
                ->icon('heroicon-m-document-currency-dollar')
                ->color('gray')
                ->url(InvoiceStegResource::getUrl('index')),
            Actions\Action::make('createFromStructure')
                ->label('Use structure')
                ->icon('heroicon-m-squares-plus')
                ->form([
                    Forms\Components\Select::make('structure_id')
                        ->label('Structure')
                        ->required()
                        ->options(function () {
                            return ParametrableInvoice::query()
                                ->where('is_structure', true)
                                ->orderBy('structure_name')
                                ->pluck('structure_name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload(),
                ])
                ->action(function (array $data) {
                    $this->redirect(ParametrableInvoiceResource::getUrl('create', [
                        'structure' => $data['structure_id'],
                    ]));
                }),
        ];
    }
}
