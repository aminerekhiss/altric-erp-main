<?php

namespace App\Filament\Company\Resources\Sales\CarResource\Pages;

use App\Filament\Company\Resources\Sales\CarResource;
use App\Models\Common\Car;
use App\Models\Common\CarCost;
use App\Models\Common\Employee;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Validation\ValidationException;

class ListCars extends ListRecords
{
    protected static string $resource = CarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('carsCost')
                ->label('Cars Cost')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->visible(fn (): bool => CarResource::canEditAny())
                ->form([
                    Forms\Components\Select::make('car_id')
                        ->label('Car')
                        ->options(function (): array {
                            $companyId = auth()->user()?->current_company_id;

                            if (! $companyId) {
                                return [];
                            }

                            return Car::query()
                                ->where('company_id', $companyId)
                                ->orderBy('car_number')
                                ->pluck('car_number', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('assurance_date')
                        ->label('Assurance date'),
                    Forms\Components\TextInput::make('assurance_amount')
                        ->label('Assurance amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('vignette_date')
                        ->label('Vignette date'),
                    Forms\Components\TextInput::make('vignette_amount')
                        ->label('Vignette amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('visite_date')
                        ->label('Visite date'),
                    Forms\Components\TextInput::make('visite_amount')
                        ->label('Visite amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('additional_cost_date')
                        ->label('Additional cost date'),
                    Forms\Components\TextInput::make('additional_cost_amount')
                        ->label('Additional cost amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\TextInput::make('additional_cost_note')
                        ->label('Additional cost note')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $companyId = auth()->user()?->current_company_id;

                    if (! $companyId) {
                        throw ValidationException::withMessages([
                            'car_id' => 'No active company selected.',
                        ]);
                    }

                    $car = Car::query()
                        ->where('company_id', $companyId)
                        ->findOrFail((int) $data['car_id']);

                    $payload = [];
                    $optionalKeys = [
                        'assurance_date',
                        'assurance_amount',
                        'vignette_date',
                        'vignette_amount',
                        'visite_date',
                        'visite_amount',
                        'additional_cost_date',
                        'additional_cost_amount',
                        'additional_cost_note',
                    ];

                    foreach ($optionalKeys as $key) {
                        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                            $payload[$key] = $data[$key];
                        }
                    }

                    if (! empty($payload)) {
                        $car->update($payload);
                    }

                    if (! empty($data['additional_cost_date']) || ! empty($data['additional_cost_amount']) || ! empty($data['additional_cost_note'])) {
                        $car->carCosts()->create([
                            'cost_type' => 'additional',
                            'cost_date' => $data['additional_cost_date'] ?? null,
                            'amount' => $data['additional_cost_amount'] ?? null,
                            'note' => $data['additional_cost_note'] ?? null,
                        ]);
                    }

                    Notification::make()
                        ->title('Car costs updated successfully')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('addMission')
                ->label('Add mission')
                ->icon('heroicon-o-map')
                ->color('primary')
                ->visible(fn (): bool => CarResource::canEditAny())
                ->form([
                    Forms\Components\Select::make('car_id')
                        ->label('Car')
                        ->options(function (): array {
                            $companyId = auth()->user()?->current_company_id;

                            if (! $companyId) {
                                return [];
                            }

                            return Car::query()
                                ->where('company_id', $companyId)
                                ->orderBy('car_number')
                                ->pluck('car_number', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('mission')
                        ->label('Mission')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('mission_date')
                        ->label('Mission date')
                        ->default(company_today()->toDateString())
                        ->required(),
                    Forms\Components\DatePicker::make('assurance_date')
                        ->label('Assurance date'),
                    Forms\Components\TextInput::make('assurance_amount')
                        ->label('Assurance amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('vignette_date')
                        ->label('Vignette date'),
                    Forms\Components\TextInput::make('vignette_amount')
                        ->label('Vignette amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('visite_date')
                        ->label('Visite date'),
                    Forms\Components\TextInput::make('visite_amount')
                        ->label('Visite amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\DatePicker::make('additional_cost_date')
                        ->label('Additional cost date'),
                    Forms\Components\TextInput::make('additional_cost_amount')
                        ->label('Additional cost amount')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('TND'),
                    Forms\Components\TextInput::make('additional_cost_note')
                        ->label('Additional cost note')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('assignment_mode')
                        ->label('Chauffeur assignment mode')
                        ->options([
                            'single' => 'Single',
                            'multiple' => 'Multiple',
                        ])
                        ->default('single')
                        ->native(false)
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('chauffeur_id')
                        ->label('Chauffeur')
                        ->options(function (): array {
                            $companyId = auth()->user()?->current_company_id;

                            if (! $companyId) {
                                return [];
                            }

                            return Employee::query()
                                ->where('company_id', $companyId)
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('assignment_mode') === 'single')
                        ->required(fn (Get $get): bool => $get('assignment_mode') === 'single'),
                    Forms\Components\Select::make('chauffeur_ids')
                        ->label('Chauffeurs')
                        ->options(function (): array {
                            $companyId = auth()->user()?->current_company_id;

                            if (! $companyId) {
                                return [];
                            }

                            return Employee::query()
                                ->where('company_id', $companyId)
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->visible(fn (Get $get): bool => $get('assignment_mode') === 'multiple')
                        ->required(fn (Get $get): bool => $get('assignment_mode') === 'multiple'),
                ])
                ->action(function (array $data): void {
                    $companyId = auth()->user()?->current_company_id;

                    if (! $companyId) {
                        throw ValidationException::withMessages([
                            'car_id' => 'No active company selected.',
                        ]);
                    }

                    $car = Car::query()
                        ->where('company_id', $companyId)
                        ->findOrFail((int) $data['car_id']);

                    $employeeIds = $data['assignment_mode'] === 'single'
                        ? [(int) $data['chauffeur_id']]
                        : array_map('intval', $data['chauffeur_ids'] ?? []);

                    if (count($employeeIds) === 0) {
                        throw ValidationException::withMessages([
                            'chauffeur_ids' => 'Please select at least one chauffeur.',
                        ]);
                    }

                    $validEmployeeIds = Employee::query()
                        ->where('company_id', $companyId)
                        ->whereIn('id', $employeeIds)
                        ->pluck('id')
                        ->map(fn (int $id): int => $id)
                        ->all();

                    if (count($validEmployeeIds) !== count(array_unique($employeeIds))) {
                        throw ValidationException::withMessages([
                            'chauffeur_ids' => 'One or more selected chauffeurs are invalid for your company.',
                        ]);
                    }

                    $payload = [
                        'mission' => $data['mission'],
                        'mission_date' => $data['mission_date'],
                    ];

                    $optionalKeys = [
                        'assurance_date',
                        'assurance_amount',
                        'vignette_date',
                        'vignette_amount',
                        'visite_date',
                        'visite_amount',
                        'additional_cost_date',
                        'additional_cost_amount',
                        'additional_cost_note',
                    ];

                    foreach ($optionalKeys as $key) {
                        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                            $payload[$key] = $data[$key];
                        }
                    }

                    $car->update($payload);

                    if (! empty($data['additional_cost_date']) || ! empty($data['additional_cost_amount']) || ! empty($data['additional_cost_note'])) {
                        $car->carCosts()->create([
                            'cost_type' => 'additional',
                            'cost_date' => $data['additional_cost_date'] ?? null,
                            'amount' => $data['additional_cost_amount'] ?? null,
                            'note' => $data['additional_cost_note'] ?? null,
                        ]);
                    }

                    $car->employees()->sync($validEmployeeIds);

                    Notification::make()
                        ->title('Mission assigned successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }
}
