<?php

namespace Database\Seeders;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Estimate;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Common\BusinessCompany;
use App\Models\Common\Car;
use App\Models\Common\Client;
use App\Models\Common\Employee;
use App\Models\Common\Offering;
use App\Models\Common\Product;
use App\Models\Common\Stock;
use App\Models\Common\StockMovement;
use App\Models\Common\Ticket;
use App\Models\Common\Vendor;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EverythingSeeder extends Seeder
{
    private const TARGET_COUNT = 3;

    public function run(): void
    {
        $faker = fake();

        Company::query()->each(function (Company $company) use ($faker): void {
            $ownerId = (int) $company->user_id;

            $this->seedEmployees($company, $ownerId);
            $this->seedBusinessCompanies($company, $ownerId);
            $this->seedCars($company, $ownerId);

            $this->seedClients($company, $ownerId);
            $this->seedVendors($company, $ownerId);
            $this->seedOfferings($company, $ownerId);
            $this->seedProducts($company, $ownerId);
            $this->seedStocks($company, $ownerId);
            $this->seedTickets($company, $ownerId);
            $this->seedStockMovements($company, $ownerId);

            $this->seedInvoices($company, $ownerId);
            $this->seedRecurringInvoices($company, $ownerId);
            $this->seedEstimates($company, $ownerId);
            $this->seedBills($company, $ownerId);
        });
    }

    private function missingCount(string $modelClass, int $companyId): int
    {
        $current = $modelClass::query()->where('company_id', $companyId)->count();

        return max(0, self::TARGET_COUNT - $current);
    }

    private function seedEmployees(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Employee::class, $company->id);

        for ($i = 0; $i < $missing; $i++) {
            Employee::query()->create([
                'company_id' => $company->id,
                'full_name' => fake()->name(),
                'email' => fake()->optional()->safeEmail(),
                'phone' => fake()->optional()->phoneNumber(),
                'rib' => fake()->optional()->numerify('########################'),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedBusinessCompanies(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(BusinessCompany::class, $company->id);

        for ($i = 0; $i < $missing; $i++) {
            BusinessCompany::query()->create([
                'company_id' => $company->id,
                'name' => fake()->company(),
                'email_primary' => fake()->optional()->companyEmail(),
                'email_secondary' => fake()->optional()->companyEmail(),
                'website' => fake()->optional()->url(),
                'phone_primary' => fake()->optional()->phoneNumber(),
                'phone_secondary' => fake()->optional()->phoneNumber(),
                'phone_tertiary' => fake()->optional()->phoneNumber(),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedCars(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Car::class, $company->id);

        for ($i = 0; $i < $missing; $i++) {
            Car::query()->create([
                'company_id' => $company->id,
                'car_number' => strtoupper('AL-' . $company->id . '-' . Str::random(6)),
                'mission' => fake()->optional()->sentence(),
                'mission_date' => fake()->optional()->date(),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedClients(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Client::class, $company->id);

        if ($missing > 0) {
            Client::factory()
                ->count($missing)
                ->withPrimaryContact()
                ->withAddresses()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedVendors(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Vendor::class, $company->id);

        if ($missing > 0) {
            Vendor::factory()
                ->count($missing)
                ->withContact()
                ->withAddress()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedProducts(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Product::class, $company->id);

        for ($i = 0; $i < $missing; $i++) {
            Product::query()->create([
                'company_id' => $company->id,
                'name' => fake()->words(3, true),
                'sku' => 'SKU-' . $company->id . '-' . strtoupper(Str::random(8)),
                'price' => fake()->numberBetween(1000, 100000),
                'cost' => fake()->numberBetween(500, 80000),
                'description' => fake()->optional()->sentence(),
                'is_active' => true,
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedOfferings(Company $company, int $ownerId): void
    {
        $sellableCount = Offering::query()
            ->where('company_id', $company->id)
            ->where('sellable', true)
            ->count();

        $purchasableCount = Offering::query()
            ->where('company_id', $company->id)
            ->where('purchasable', true)
            ->count();

        $missing = max(0, self::TARGET_COUNT - min($sellableCount, $purchasableCount));

        if ($missing > 0) {
            Offering::factory()
                ->count($missing)
                ->withSalesAdjustments()
                ->withPurchaseAdjustments()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedStocks(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Stock::class, $company->id);
        $products = Product::query()->where('company_id', $company->id)->get();

        if ($products->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $product = $products[$i % $products->count()];

            Stock::query()->create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'quantity' => fake()->numberBetween(1, 200),
                'location' => fake()->optional()->city(),
                'notes' => fake()->optional()->sentence(),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedTickets(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Ticket::class, $company->id);
        $products = Product::query()->where('company_id', $company->id)->get();

        if ($products->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $product = $products[$i % $products->count()];

            Ticket::query()->create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'type' => fake()->randomElement([Ticket::TYPE_ENTRANCE, Ticket::TYPE_EXIT]),
                'status' => fake()->randomElement([Ticket::STATUS_DRAFT, Ticket::STATUS_VALIDATED]),
                'name' => 'Ticket ' . strtoupper(Str::random(6)),
                'provider' => fake()->optional()->company(),
                'date' => fake()->date(),
                'quantity' => fake()->numberBetween(1, 50),
                'notes' => fake()->optional()->sentence(),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);
        }
    }

    private function seedStockMovements(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(StockMovement::class, $company->id);
        $tickets = Ticket::query()->where('company_id', $company->id)->get();
        $stocks = Stock::query()->where('company_id', $company->id)->get();

        if ($tickets->isEmpty() || $stocks->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $ticket = $tickets[$i % $tickets->count()];
            $stock = $stocks[$i % $stocks->count()];
            $quantity = fake()->numberBetween(1, 20);
            $before = (int) $stock->quantity;
            $direction = $ticket->type === Ticket::TYPE_EXIT ? 'out' : 'in';
            $after = $direction === 'out' ? max(0, $before - $quantity) : $before + $quantity;

            StockMovement::query()->create([
                'company_id' => $company->id,
                'ticket_id' => $ticket->id,
                'product_id' => $stock->product_id,
                'stock_id' => $stock->id,
                'direction' => $direction,
                'operation' => 'apply',
                'quantity' => $quantity,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'notes' => fake()->optional()->sentence(),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $stock->updateQuietly(['quantity' => $after]);
        }
    }

    private function seedInvoices(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Invoice::class, $company->id);

        if ($missing > 0) {
            Invoice::factory()
                ->count($missing)
                ->withLineItems()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedRecurringInvoices(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(RecurringInvoice::class, $company->id);

        if ($missing > 0) {
            RecurringInvoice::factory()
                ->count($missing)
                ->withSchedule()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedEstimates(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Estimate::class, $company->id);

        if ($missing > 0) {
            Estimate::factory()
                ->count($missing)
                ->withLineItems()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }

    private function seedBills(Company $company, int $ownerId): void
    {
        $missing = $this->missingCount(Bill::class, $company->id);

        if ($missing > 0) {
            Bill::factory()
                ->count($missing)
                ->withLineItems()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);
        }
    }
}
