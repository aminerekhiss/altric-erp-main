<?php

namespace App\Support;

use App\Models\Common\Employee;
use App\Models\Company;
use App\Models\Setting\CompanyDefault;
use App\Models\User;

class EmployeeModuleAccess
{
    public const MODULE_BUSINESS_COMPANIES = 'business_companies';

    public const MODULE_EMPLOYEES = 'employees';

    public const MODULE_EMPLOYEE_ABSENCES = 'employee_absences';

    public const MODULE_CARS = 'cars';

    public const MODULE_EMPLOYEE_ATTENDANCES = 'employee_attendances';

    public const MODULE_EMPLOYEE_WEEK_OFFS = 'employee_week_offs';

    public const MODULE_EMPLOYEE_SALARIES = 'employee_salaries';

    public const MODULE_MESSAGES = 'messages';

    public const MODULE_PRODUCTS = 'products';

    public const MODULE_STOCKS = 'stocks';

    public const MODULE_TICKETS = 'tickets';

    public const MODULE_INVOICE_ARCHIVES = 'invoice_archives';

    public const MODULE_STOCK_MOVEMENTS = 'stock_movements';

    public const MODULE_PROJECTS = 'projects';

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return [
            self::MODULE_BUSINESS_COMPANIES => true,
            self::MODULE_EMPLOYEES => true,
            self::MODULE_EMPLOYEE_ABSENCES => true,
            self::MODULE_CARS => true,
            self::MODULE_EMPLOYEE_ATTENDANCES => true,
            self::MODULE_EMPLOYEE_WEEK_OFFS => true,
            self::MODULE_EMPLOYEE_SALARIES => true,
            self::MODULE_MESSAGES => true,
            self::MODULE_PRODUCTS => true,
            self::MODULE_STOCKS => true,
            self::MODULE_TICKETS => true,
            self::MODULE_INVOICE_ARCHIVES => true,
            self::MODULE_STOCK_MOVEMENTS => true,
            self::MODULE_PROJECTS => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::MODULE_BUSINESS_COMPANIES => translate('Companies'),
            self::MODULE_EMPLOYEES => translate('Employees'),
            self::MODULE_EMPLOYEE_ABSENCES => translate('Absences / Conges'),
            self::MODULE_CARS => translate('Cars'),
            self::MODULE_EMPLOYEE_ATTENDANCES => translate('Attendance'),
            self::MODULE_EMPLOYEE_WEEK_OFFS => translate('Weeks Off'),
            self::MODULE_EMPLOYEE_SALARIES => translate('Salaries'),
            self::MODULE_MESSAGES => translate('Messages'),
            self::MODULE_PRODUCTS => translate('Products'),
            self::MODULE_STOCKS => translate('Stock'),
            self::MODULE_TICKETS => translate('Tickets'),
            self::MODULE_INVOICE_ARCHIVES => translate('Invoice Archives'),
            self::MODULE_STOCK_MOVEMENTS => translate('Stock Movements'),
            self::MODULE_PROJECTS => translate('Projects'),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $access
     * @return array<string, bool>
     */
    public static function normalize(?array $access): array
    {
        $normalized = self::defaults();

        if (! is_array($access)) {
            return $normalized;
        }

        foreach (array_keys(self::defaults()) as $moduleKey) {
            if (array_key_exists($moduleKey, $access)) {
                $normalized[$moduleKey] = (bool) $access[$moduleKey];
            }
        }

        return $normalized;
    }

    public static function allows(string $moduleKey, ?User $user = null, ?Company $company = null): bool
    {
        $user ??= auth()->user();
        $company ??= $user?->currentCompany;

        if (! $user || ! $company) {
            return false;
        }

        if ($user->ownsCompany($company)) {
            return true;
        }

        $roleKey = $user->companyRole($company)?->key;

        // Restrict only employee role; company/admin/editor keep role-based permissions.
        if ($roleKey !== 'employee') {
            return true;
        }

        $settings = CompanyDefault::query()
            ->where('company_id', $company->id)
            ->first();

        $access = self::normalize($settings?->employee_module_access);

        $employeeOverride = Employee::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->value('employee_module_access');

        if (is_array($employeeOverride)) {
            $access = self::normalize(array_merge($access, $employeeOverride));
        }

        return (bool) ($access[$moduleKey] ?? true);
    }
}
