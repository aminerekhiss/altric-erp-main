<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\Pages;

use App\Filament\Company\Resources\Purchases\BillResource\Pages\CreateBill;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Filament\Company\Resources\Purchases\VendorResource\RelationManagers;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getAllRelationManagers(): array
    {
        return [
            RelationManagers\BillsRelationManager::class,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(translate('Edit vendor'))
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Action::make('newBill')
                        ->label(translate('New bill'))
                        ->icon('heroicon-m-document-plus')
                        ->url(CreateBill::getUrl(['vendor' => $this->record->getKey()])),
                ])->dropdown(false),
                DeleteAction::make(),
            ])
                ->label(translate('Actions'))
                ->button()
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VendorResource\Widgets\BillOverview::class,
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('General')
                    ->columns()
                    ->schema([
                        TextEntry::make('contact.full_name')
                            ->label(translate('Contact')),
                        TextEntry::make('contact.email')
                            ->label(translate('Email')),
                        TextEntry::make('contact.first_available_phone')
                            ->label(translate('Primary phone')),
                        TextEntry::make('website')
                            ->label(translate('Website'))
                            ->url(static fn ($state) => $state, true)
                            ->link(),
                    ]),
                Section::make('Additional Details')
                    ->columns()
                    ->schema([
                        TextEntry::make('address.address_string')
                            ->label(translate('Billing address'))
                            ->listWithLineBreaks(),
                        TextEntry::make('notes'),
                    ]),
            ]);
    }
}
