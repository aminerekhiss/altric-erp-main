<?php

namespace App\Filament\Company\Pages\Service;

use App\Models\Service\WorkflowRecord;
use App\Services\GrokWorkflowService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class Workflow extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Workflow';

    protected static ?string $slug = 'services/workflow';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.company.pages.service.workflow';

    public ?array $data = [];

    public ?array $workflow = null;

    public array $history = [];

    public function mount(): void
    {
        $this->form->fill([
            'workflow_name' => '',
            'department' => 'facturation',
            'goal' => '',
            'constraints' => '',
        ]);

        $this->loadHistory();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('workflow_name')
                    ->label('Workflow Name')
                    ->required()
                    ->maxLength(120),
                Select::make('department')
                    ->label('Module')
                    ->options([
                        'facturation' => 'Facturation',
                        'finance' => 'Finance',
                        'achat' => 'Achat',
                        'rh' => 'Ressources Humaines',
                        'inventaire' => 'Inventaire',
                        'comptabilite' => 'Comptabilite',
                    ])
                    ->required(),
                Textarea::make('goal')
                    ->label('Goal')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Textarea::make('constraints')
                    ->label('Constraints / Notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function generateWorkflow(): void
    {
        $state = $this->form->getState();

        $prompt = implode("\n", [
            'Create a practical ERP workflow in JSON format.',
            'Workflow Name: ' . $state['workflow_name'],
            'Module: ' . $state['department'],
            'Goal: ' . $state['goal'],
            'Constraints: ' . ($state['constraints'] ?: 'None'),
            'Each step should be actionable for employees.',
        ]);

        try {
            $this->workflow = app(GrokWorkflowService::class)->generate($prompt);

            Notification::make()
                ->success()
                ->title('Workflow generated')
                ->body('Grok created a workflow successfully.')
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Workflow generation failed')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function saveWorkflow(): void
    {
        if (! is_array($this->workflow)) {
            Notification::make()
                ->warning()
                ->title('Nothing to save')
                ->body('Generate a workflow first.')
                ->send();

            return;
        }

        $state = $this->form->getState();

        WorkflowRecord::query()->create([
            'user_id' => auth()->id(),
            'name' => (string) ($state['workflow_name'] ?? 'Workflow'),
            'module' => (string) ($state['department'] ?? ''),
            'goal' => (string) ($state['goal'] ?? ''),
            'constraints' => (string) ($state['constraints'] ?? ''),
            'workflow_title' => (string) ($this->workflow['title'] ?? ''),
            'workflow_summary' => (string) ($this->workflow['summary'] ?? ''),
            'workflow_steps' => is_array($this->workflow['steps'] ?? null) ? $this->workflow['steps'] : [],
            'grok_raw_response' => (string) ($this->workflow['raw'] ?? ''),
        ]);

        $this->loadHistory();

        Notification::make()
            ->success()
            ->title('Workflow saved')
            ->body('Workflow has been saved to history.')
            ->send();
    }

    public function loadHistoryItem(int $id): void
    {
        $record = WorkflowRecord::query()->findOrFail($id);

        $this->form->fill([
            'workflow_name' => $record->name,
            'department' => $record->module ?: 'facturation',
            'goal' => $record->goal ?: '',
            'constraints' => $record->constraints ?: '',
        ]);

        $this->workflow = [
            'title' => $record->workflow_title ?: $record->name,
            'summary' => $record->workflow_summary ?: '',
            'steps' => is_array($record->workflow_steps) ? $record->workflow_steps : [],
            'raw' => $record->grok_raw_response ?: '',
        ];
    }

    protected function loadHistory(): void
    {
        $this->history = WorkflowRecord::query()
            ->latest('id')
            ->limit(20)
            ->get(['id', 'name', 'module', 'created_at'])
            ->map(static fn (WorkflowRecord $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'module' => $item->module,
                'created_at' => optional($item->created_at)->toDateTimeString(),
            ])
            ->all();
    }

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public static function canAccess(): bool
    {
        return true;
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->visible(static::canAccess())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getNavigationUrl()),
        ];
    }
}
