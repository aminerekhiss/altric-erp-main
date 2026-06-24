<?php

namespace App\Filament\Company\Pages;

use App\Models\Common\Client;
use App\Models\Common\SmartCrmLead;
use App\Services\GrokInsightsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SmartCrm extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Smart CRM';

    protected static ?string $navigationLabel = 'Smart CRM';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $slug = 'smart-crm';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationGroup = 'Facturation';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.company.pages.smart-crm';

    public ?array $leadData = [];

    public ?string $aiInsights = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $company = $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        return $user->ownsCompany($company) || $user->hasCompanyPermission($company, 'read');
    }

    public function mount(): void
    {
        $this->form->fill([
            'name' => '',
            'email' => '',
            'phone' => '',
            'source' => 'web',
            'status' => 'new',
            'expected_value' => null,
            'client_id' => null,
            'notes' => '',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateAiInsights')
                ->label('AI Churn Insights')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(fn () => $this->generateAiInsights()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Lead Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone')
                    ->maxLength(60),
                Select::make('source')
                    ->label('Source')
                    ->options([
                        'web' => 'Web',
                        'social' => 'Social',
                        'referral' => 'Referral',
                        'campaign' => 'Campaign',
                        'call' => 'Call',
                    ])
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'contacted' => 'Contacted',
                        'qualified' => 'Qualified',
                        'proposal' => 'Proposal',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->required(),
                TextInput::make('expected_value')
                    ->label('Expected Value')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('TND'),
                Select::make('client_id')
                    ->label('Existing Client (optional)')
                    ->searchable()
                    ->options(function (): array {
                        $companyId = auth()->user()?->current_company_id;

                        return Client::query()
                            ->where('company_id', $companyId)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->statePath('leadData');
    }

    public function createLead(): void
    {
        $state = $this->form->getState();

        SmartCrmLead::query()->create([
            'client_id' => $state['client_id'] ?? null,
            'name' => (string) $state['name'],
            'email' => $state['email'] ?? null,
            'phone' => $state['phone'] ?? null,
            'source' => $state['source'] ?? null,
            'status' => $state['status'] ?? 'new',
            'expected_value' => $state['expected_value'] ?? null,
            'notes' => $state['notes'] ?? null,
        ]);

        Notification::make()
            ->success()
            ->title('Lead created')
            ->body('Lead has been added to Smart CRM.')
            ->send();

        $this->form->fill([
            'name' => '',
            'email' => '',
            'phone' => '',
            'source' => 'web',
            'status' => 'new',
            'expected_value' => null,
            'client_id' => null,
            'notes' => '',
        ]);
    }

    public function generateAiInsights(): void
    {
        $data = $this->buildClientInsights();

        if (($data['clients_count'] ?? 0) === 0) {
            Notification::make()
                ->warning()
                ->title('No clients to analyze')
                ->send();

            return;
        }

        $prompt = implode("\n", [
            'You are a CRM strategist. Analyze churn and activity data and return concise recommendations.',
            'Total clients: ' . $data['clients_count'],
            'Average churn percent: ' . number_format((float) $data['avg_churn_percent'], 2, '.', ''),
            'Average activity score: ' . number_format((float) $data['avg_activity_score'], 2, '.', ''),
            'At-risk clients (>=65%): ' . $data['at_risk_count'],
            'Top risk names: ' . implode(', ', $data['top_risk_names']),
            'Output in French with sections: Resume, Risques, Actions prioritaires.',
        ]);

        try {
            $this->aiInsights = app(GrokInsightsService::class)->generateInsights($prompt);

            Notification::make()
                ->success()
                ->title('AI insights generated')
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('AI insights failed')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function getViewData(): array
    {
        $insights = $this->buildClientInsights();

        $leads = SmartCrmLead::query()
            ->latest('id')
            ->limit(15)
            ->get();

        return [
            'insights' => $insights,
            'leads' => $leads,
            'aiInsights' => $this->aiInsights,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClientInsights(): array
    {
        $companyId = auth()->user()?->current_company_id;

        if (! $companyId) {
            return [
                'clients_count' => 0,
                'avg_churn_percent' => 0,
                'avg_activity_score' => 0,
                'at_risk_count' => 0,
                'top_risk_names' => [],
                'top_clients' => [],
                'churn_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
                'churn_bucket_values' => [0, 0, 0, 0],
                'activity_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
                'activity_bucket_values' => [0, 0, 0, 0],
            ];
        }

        $today = company_today();
        $start90 = $today->copy()->subDays(90)->startOfDay();
        $start180 = $today->copy()->subDays(180)->startOfDay();

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->with(['invoices' => function ($query) use ($start180) {
                $query->whereDate('date', '>=', $start180->toDateString())
                    ->select(['id', 'client_id', 'date', 'total', 'amount_paid']);
            }])
            ->get();

        if ($clients->isEmpty()) {
            return [
                'clients_count' => 0,
                'avg_churn_percent' => 0,
                'avg_activity_score' => 0,
                'at_risk_count' => 0,
                'top_risk_names' => [],
                'top_clients' => [],
                'churn_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
                'churn_bucket_values' => [0, 0, 0, 0],
                'activity_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
                'activity_bucket_values' => [0, 0, 0, 0],
            ];
        }

        $maxRecentAmount = 0.0;
        $clientStats = [];

        foreach ($clients as $client) {
            $invoices = $client->invoices;
            $lastInvoice = $invoices->sortByDesc('date')->first();

            $recentInvoices = $invoices->filter(fn ($i) => $i->date && $i->date->gte($start90));
            $previousInvoices = $invoices->filter(fn ($i) => $i->date && $i->date->lt($start90) && $i->date->gte($start180));

            $recentAmount = (float) $recentInvoices->sum('total');
            $recentPaid = (float) $recentInvoices->sum('amount_paid');
            $prevAmount = (float) $previousInvoices->sum('total');
            $recentCount = (int) $recentInvoices->count();

            if ($recentAmount > $maxRecentAmount) {
                $maxRecentAmount = $recentAmount;
            }

            $daysSinceLast = $lastInvoice?->date ? (int) $lastInvoice->date->diffInDays($today) : 365;

            $clientStats[] = [
                'id' => $client->id,
                'name' => $client->name,
                'days_since_last' => $daysSinceLast,
                'recent_amount' => $recentAmount,
                'recent_paid' => $recentPaid,
                'prev_amount' => $prevAmount,
                'recent_count' => $recentCount,
            ];
        }

        $results = collect($clientStats)->map(function (array $row) use ($maxRecentAmount): array {
            $unpaidRatio = $row['recent_amount'] > 0
                ? max(0, min(1, 1 - ($row['recent_paid'] / $row['recent_amount'])))
                : 0.6;

            $dropRatio = $row['prev_amount'] > 0
                ? max(0, min(1, 1 - ($row['recent_amount'] / $row['prev_amount'])))
                : ($row['recent_amount'] > 0 ? 0.1 : 0.6);

            $recencyRisk = min(1, $row['days_since_last'] / 180);
            $churn = round((0.45 * $recencyRisk + 0.30 * $dropRatio + 0.25 * $unpaidRatio) * 100, 2);

            $recencyScore = max(0, 100 - min(100, ($row['days_since_last'] / 180) * 100));
            $frequencyScore = min(100, $row['recent_count'] * 12.5);
            $monetaryScore = $maxRecentAmount > 0 ? min(100, ($row['recent_amount'] / $maxRecentAmount) * 100) : 0;
            $activity = round((0.40 * $recencyScore) + (0.35 * $frequencyScore) + (0.25 * $monetaryScore), 2);

            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'churn_percent' => $churn,
                'activity_score' => $activity,
                'recent_amount' => round($row['recent_amount'], 3),
                'recent_count' => $row['recent_count'],
            ];
        });

        $avgChurn = round((float) $results->avg('churn_percent'), 2);
        $avgActivity = round((float) $results->avg('activity_score'), 2);
        $atRiskCount = $results->filter(fn (array $r) => $r['churn_percent'] >= 65)->count();

        $topClients = $results->sortByDesc('churn_percent')->take(10)->values()->all();
        $topRiskNames = collect($topClients)->take(5)->pluck('name')->all();

        $churnBuckets = [0, 0, 0, 0];
        $activityBuckets = [0, 0, 0, 0];

        foreach ($results as $row) {
            $churnBuckets[$this->bucketIndex((float) $row['churn_percent'])]++;
            $activityBuckets[$this->bucketIndex((float) $row['activity_score'])]++;
        }

        return [
            'clients_count' => $results->count(),
            'avg_churn_percent' => $avgChurn,
            'avg_activity_score' => $avgActivity,
            'at_risk_count' => $atRiskCount,
            'top_risk_names' => $topRiskNames,
            'top_clients' => $topClients,
            'churn_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
            'churn_bucket_values' => $churnBuckets,
            'activity_bucket_labels' => ['0-25', '26-50', '51-75', '76-100'],
            'activity_bucket_values' => $activityBuckets,
        ];
    }

    private function bucketIndex(float $value): int
    {
        if ($value <= 25) {
            return 0;
        }

        if ($value <= 50) {
            return 1;
        }

        if ($value <= 75) {
            return 2;
        }

        return 3;
    }
}
