<?php

namespace App\Services\Taxation;

use App\Enums\EmploymentTypesEnum;
use App\Models\Bir2316;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Bir2316Service
{
    public function __construct(
        private readonly IndividualTaxDataService $individualTaxDataService
    ) {}

    public function getPagePayload(array $filters = []): array
    {
        $availableYears = $this->availableYears();
        $selectedYear = $this->individualTaxDataService->resolveSelectedYear(
            isset($filters['taxable_year']) ? (int) $filters['taxable_year'] : null,
            $availableYears
        );
        $rows = $this->buildRows([
            ...$filters,
            'taxable_year' => $selectedYear,
        ]);

        return [
            'filters' => [
                'taxable_year' => $selectedYear,
                'employee_id' => isset($filters['employee_id']) ? (int) $filters['employee_id'] : null,
                'division_id' => isset($filters['division_id']) ? (int) $filters['division_id'] : null,
                'employment_type_id' => isset($filters['employment_type_id']) ? (int) $filters['employment_type_id'] : null,
                'status' => $filters['status'] ?? '',
            ],
            'availableYears' => $availableYears->push($selectedYear)->unique()->sortDesc()->values()->all(),
            'employees' => $this->employeeOptions(),
            'divisions' => $this->divisionOptions(),
            'employmentTypes' => $this->employmentTypeOptions(),
            'statuses' => $this->statusOptions(),
            'rows' => $rows,
            'summary' => $this->summarizeRows($rows),
        ];
    }

    public function buildRows(array $filters = []): array
    {
        $year = (int) ($filters['taxable_year'] ?? now()->year);
        $records = Bir2316::query()
            ->where('taxable_year', $year)
            ->get()
            ->keyBy('employee_id');

        return $this->baseEmployeeQuery()
            ->when(!empty($filters['employee_id']), fn (Builder $query) => $query->where('ei.id', (int) $filters['employee_id']))
            ->when(!empty($filters['division_id']), fn (Builder $query) => $query->where('d.id', (int) $filters['division_id']))
            ->when(!empty($filters['employment_type_id']), fn (Builder $query) => $query->where('et.id', (int) $filters['employment_type_id']))
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->orderBy('ei.employee_no')
            ->get()
            ->map(function ($employee) use ($records, $filters, $year) {
                $record = $records->get((int) $employee->id);
                $statusFilter = trim((string) ($filters['status'] ?? ''));
                $status = $record?->status ?? 'not_generated';

                if ($statusFilter !== '' && $status !== $statusFilter) {
                    return null;
                }

                return [
                    'employee_id' => (int) $employee->id,
                    'employee_no' => (string) $employee->employee_no,
                    'employee_name' => $this->formatEmployeeName((array) $employee),
                    'tin' => $employee->tin_no,
                    'position' => $employee->position_name ?: 'N/A',
                    'division_name' => $employee->division_name ?: 'No Division',
                    'employment_type' => $employee->employment_type_name ?: 'N/A',
                    'taxable_year' => $year,
                    'net_taxable_income' => (float) ($record?->net_taxable_income ?? 0),
                    'annual_tax_due' => (float) ($record?->annual_tax_due ?? 0),
                    'tax_withheld' => (float) ($record?->tax_withheld ?? 0),
                    'tax_refund_or_payable' => (float) ($record?->tax_refund_or_payable ?? 0),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'record_id' => $record?->id,
                    'generated_at' => optional($record?->generated_at)->toDateTimeString(),
                    'locked_at' => optional($record?->locked_at)->toDateTimeString(),
                    'can_generate' => $status !== 'locked',
                    'can_lock' => in_array($status, ['generated', 'draft'], true),
                    'can_unlock' => $status === 'locked',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function findOrFail(int $id): Bir2316
    {
        return Bir2316::query()->findOrFail($id);
    }

    public function availableYears(): Collection
    {
        $taxationYears = Schema::hasTable('n_taxation')
            ? $this->individualTaxDataService->getAvailableTaxationYears()->all()
            : [];

        return collect(array_merge(
            $taxationYears,
            Bir2316::query()->pluck('taxable_year')->map(fn ($year) => (int) $year)->all()
        ))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function employeeOptions(): array
    {
        return $this->baseEmployeeQuery()
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->get()
            ->map(fn ($employee) => [
                'id' => (int) $employee->id,
                'employee_no' => (string) $employee->employee_no,
                'name' => $this->formatEmployeeName((array) $employee),
            ])
            ->values()
            ->all();
    }

    public function divisionOptions(): array
    {
        return DB::table('divisions')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($division) => [
                'id' => (int) $division->id,
                'name' => (string) $division->name,
            ])
            ->values()
            ->all();
    }

    public function employmentTypeOptions(): array
    {
        return DB::table('employment_types')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($type) => [
                'id' => (int) $type->id,
                'name' => (string) $type->name,
            ])
            ->values()
            ->all();
    }

    public function statusOptions(): array
    {
        return collect([
            'not_generated',
            'draft',
            'generated',
            'locked',
            'cancelled',
        ])->map(fn (string $status) => [
            'value' => $status,
            'label' => $this->statusLabel($status),
        ])->values()->all();
    }

    public function summarizeRows(array $rows): array
    {
        return [
            'net_taxable_income' => (float) collect($rows)->sum('net_taxable_income'),
            'annual_tax_due' => (float) collect($rows)->sum('annual_tax_due'),
            'tax_withheld' => (float) collect($rows)->sum('tax_withheld'),
            'tax_refund_or_payable' => (float) collect($rows)->sum('tax_refund_or_payable'),
        ];
    }

    public function lock(Bir2316 $bir2316, ?int $userId = null): Bir2316
    {
        $bir2316->forceFill([
            'status' => 'locked',
            'locked_at' => now(),
            'updated_by' => $userId,
        ])->save();

        return $bir2316->refresh();
    }

    public function unlock(Bir2316 $bir2316, ?int $userId = null): Bir2316
    {
        $bir2316->forceFill([
            'status' => 'generated',
            'locked_at' => null,
            'updated_by' => $userId,
        ])->save();

        return $bir2316->refresh();
    }

    private function baseEmployeeQuery(): Builder
    {
        $latestOrgDate = DB::table('employee_organization')
            ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
            ->groupBy('employee_no');

        $latestOrgId = DB::table('employee_organization')
            ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
            ->groupBy('employee_no', 'effectivity_date');

        return DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                $join->on('ei.employee_no', '=', 'latest_org_date.employee_no');
            })
            ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                    ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
            })
            ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('employment_types as et', 'eo.employment_type_id', '=', 'et.id')
            ->where('ei.account_status', 'active')
            ->where('ei.isDeleted', false)
            ->where('eo.employment_type_id', EmploymentTypesEnum::REGULAR->value)
            ->select(
                'ei.id',
                'ei.employee_no',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'ep.tin_no',
                'd.id as division_id',
                'd.name as division_name',
                'p.name as position_name',
                'et.id as employment_type_id',
                'et.name as employment_type_name',
            );
    }

    private function formatEmployeeName(array $employee): string
    {
        $lastname = trim((string) ($employee['lastname'] ?? ''));
        $firstname = trim((string) ($employee['firstname'] ?? ''));
        $middlename = trim((string) ($employee['middlename'] ?? ''));
        $suffix = trim((string) ($employee['suffix'] ?? ''));
        $middleInitial = $middlename !== '' ? ' ' . strtoupper(substr($middlename, 0, 1)) . '.' : '';
        $suffixText = $suffix !== '' ? ' ' . $suffix : '';

        return trim(sprintf('%s, %s%s%s', $lastname, $firstname, $middleInitial, $suffixText), ', ');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'generated' => 'Generated',
            'locked' => 'Locked',
            'cancelled' => 'Cancelled',
            default => 'Not Generated',
        };
    }
}
