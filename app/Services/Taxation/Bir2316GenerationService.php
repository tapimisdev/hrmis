<?php

namespace App\Services\Taxation;

use App\Models\Bir2316;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Bir2316GenerationService
{
    public function __construct(
        private readonly Bir2316Service $bir2316Service,
        private readonly IndividualTaxDataService $individualTaxDataService
    ) {}

    public function generate(array $payload, ?int $userId = null): array
    {
        $year = (int) data_get($payload, 'taxable_year');
        $employees = $this->resolveEmployees($payload, $year);

        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_ids' => ['No employees were selected for BIR 2316 generation.'],
            ]);
        }

        $generated = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $generated[] = DB::transaction(function () use ($employee, $year, $userId) {
                    $source = $this->buildSourceSnapshot((int) $employee->id, $year);
                    $existing = Bir2316::query()
                        ->where('employee_id', (int) $employee->id)
                        ->where('taxable_year', $year)
                        ->first();

                    if ($existing && $existing->status === 'locked') {
                        throw ValidationException::withMessages([
                            'employee_ids' => [sprintf(
                                'BIR 2316 for %s (%s) is locked and cannot be regenerated.',
                                $source['employee_name'],
                                $source['employee_no']
                            )],
                        ]);
                    }

                    $record = Bir2316::query()->updateOrCreate(
                        [
                            'employee_id' => (int) $employee->id,
                            'taxable_year' => $year,
                        ],
                        [
                            ...$source,
                            'status' => 'generated',
                            'generated_at' => now(),
                            'locked_at' => null,
                            'created_by' => $existing?->created_by ?? $userId,
                            'updated_by' => $userId,
                        ]
                    );

                    return $record->fresh();
                });
            } catch (ValidationException $exception) {
                $errors[] = $exception->errors()['employee_ids'][0] ?? $exception->getMessage();
            }
        }

        if ($generated === [] && $errors !== []) {
            throw ValidationException::withMessages([
                'employee_ids' => $errors,
            ]);
        }

        return [
            'message' => sprintf('Generated %d BIR 2316 record(s).', count($generated)),
            'generated_ids' => collect($generated)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'errors' => $errors,
        ];
    }

    private function resolveEmployees(array $payload, int $year): Collection
    {
        if ((bool) data_get($payload, 'all_employees', false)) {
            $taxationPayload = $this->individualTaxDataService->getPagePayload(null, $year);
            $employeeNos = collect((array) data_get($taxationPayload, 'employees', []))
                ->map(fn ($employee) => (string) data_get($employee, 'employee_no'))
                ->filter()
                ->unique()
                ->values();

            return DB::table('employee_information')
                ->whereIn('employee_no', $employeeNos->all())
                ->get(['id', 'employee_no']);
        }

        return DB::table('employee_information')
            ->whereIn('id', (array) data_get($payload, 'employee_ids', []))
            ->get(['id', 'employee_no']);
    }

    private function buildSourceSnapshot(int $employeeId, int $year): array
    {
        $employee = $this->employeeDetails($employeeId);

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee_ids' => ['Selected employee does not exist.'],
            ]);
        }

        $taxationPayload = $this->individualTaxDataService->getPagePayload((string) $employee->employee_no, $year);

        if (!(bool) ($taxationPayload['hasTaxationData'] ?? false)) {
            throw ValidationException::withMessages([
                'employee_ids' => [sprintf('No annual tax computation exists for %s in %d.', $employee->employee_no, $year)],
            ]);
        }

        $selectedEmployee = (object) ($taxationPayload['employee'] ?? []);

        if (($selectedEmployee->employee_no ?? null) !== $employee->employee_no) {
            throw ValidationException::withMessages([
                'employee_ids' => [sprintf('No annual tax computation exists for %s in %d.', $employee->employee_no, $year)],
            ]);
        }

        $summary = (array) ($taxationPayload['summary'] ?? []);

        $otherComponents = (array) ($taxationPayload['otherComponents'] ?? []);
        $annualTaxComputationId = DB::table('n_taxation_employees as nte')
            ->join('n_taxation as nt', 'nt.id', '=', 'nte.n_taxation_id')
            ->where('nt.Year', $year)
            ->where('nte.employee_no', $employee->employee_no)
            ->value('nte.id');

        $employer = $this->employerDetails();
        $employeeAddress = $this->combineAddress([
            $employee->present_block,
            $employee->present_street,
            $employee->present_subdivision,
            $employee->present_barangay,
            $employee->present_city,
            $employee->present_province,
            $employee->present_zip,
        ]) ?: $this->combineAddress([
            $employee->permanent_block,
            $employee->permanent_street,
            $employee->permanent_subdivision,
            $employee->permanent_barangay,
            $employee->permanent_city,
            $employee->permanent_province,
            $employee->permanent_zip,
        ]);
        $allowables = $this->normalizeAllowables((array) data_get($otherComponents, 'allowables', []));
        $taxDue = (float) data_get($summary, 'annual_tax_due', 0);
        $taxWithheld = (float) data_get($summary, 'total_tax_withheld', 0);
        $refundOrPayable = round($taxWithheld - $taxDue, 2);
        $nonTaxableCompensation = round(
            (float) data_get($summary, 'tax_exempt_bonus', 0) + (float) data_get($summary, 'de_minimis', 0),
            2
        );
        $otherTaxableCompensation = round(max(
            0,
            (float) data_get($summary, 'gross_taxable_income', 0)
            - (float) data_get($summary, 'annual_basic_salary', 0)
            - (float) data_get($summary, 'hazard_pay', 0)
            - (float) data_get($summary, 'longevity_pay', 0)
            - (float) data_get($summary, 'net_taxable_benefit', 0)
        ), 2);
        $otherNonTaxableCompensation = round(max(
            0,
            $nonTaxableCompensation - (float) data_get($summary, 'government_bonuses', 0)
        ), 2);

        $data = [
            'annual_tax_computation_id' => $annualTaxComputationId ? (int) $annualTaxComputationId : null,
            'employee_no' => (string) $employee->employee_no,
            'employee_name' => $this->formatEmployeeName($employee),
            'employee_tin' => $employee->tin_no,
            'employee_address' => $employeeAddress,
            'position' => $employee->position_name,
            'employment_type' => $employee->employment_type_name,
            'employer_name' => $employer['name'],
            'employer_tin' => $employer['tin'],
            'employer_address' => $employer['address'],
            'rdo_code' => $employer['rdo_code'],
            'annual_basic_salary' => (float) data_get($summary, 'annual_basic_salary', 0),
            'hazard_pay' => (float) data_get($summary, 'hazard_pay', 0),
            'longevity_pay' => (float) data_get($summary, 'longevity_pay', 0),
            'government_bonuses' => (float) data_get($summary, 'government_bonuses', 0),
            'de_minimis' => (float) data_get($summary, 'de_minimis', 0),
            'gross_compensation_income' => (float) data_get($summary, 'gross_compensation_income', 0),
            'tax_exempt_bonus' => (float) data_get($summary, 'tax_exempt_bonus', 0),
            'net_taxable_benefit' => (float) data_get($summary, 'net_taxable_benefit', 0),
            'gross_taxable_income' => (float) data_get($summary, 'gross_taxable_income', 0),
            'allowable_deductions' => (float) data_get($summary, 'allowable_deductions', 0),
            'net_taxable_income' => (float) data_get($summary, 'net_taxable_income', 0),
            'annual_tax_due' => $taxDue,
            'tax_withheld' => $taxWithheld,
            'tax_refund_or_payable' => $refundOrPayable,
            'snapshot_data' => [
                'employee' => [
                    'employee_id' => $employeeId,
                    'employee_no' => (string) $employee->employee_no,
                'employee_name' => $this->formatEmployeeName($employee),
                'tin' => $employee->tin_no,
                'address' => $employeeAddress,
                'birth_date' => $employee->birthday,
                'contact_number' => $employee->mobile_number,
                'zip_code' => $employee->present_zip ?: $employee->permanent_zip,
                'position' => $employee->position_name,
                'employment_type' => $employee->employment_type_name,
            ],
                'employer' => $employer,
                'summary' => $summary,
                'other_components' => $otherComponents,
                'pdf_values' => [
                    'non_taxable_compensation' => $nonTaxableCompensation,
                    'other_taxable_compensation' => $otherTaxableCompensation,
                    'other_nontaxable_compensation' => $otherNonTaxableCompensation,
                    'tax_refund' => $refundOrPayable > 0 ? $refundOrPayable : 0,
                    'tax_payable' => $refundOrPayable < 0 ? abs($refundOrPayable) : 0,
                    'allowables' => $allowables,
                    'total_contributions' => round(array_sum($allowables), 2),
                ],
                'monthly_breakdown' => (array) ($taxationPayload['monthlyBreakdown'] ?? []),
                'certification' => [
                    'authorized_signatory' => optional(auth()->user())->name ?? 'System Administrator',
                    'employee_signature' => $this->formatEmployeeName($employee),
                    'date_signed' => now()->toDateString(),
                    'substitute_filing' => false,
                ],
            ],
        ];

        return $data;
    }

    private function employeeDetails(int $employeeId): ?object
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
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('employment_types as et', 'eo.employment_type_id', '=', 'et.id')
            ->where('ei.id', $employeeId)
            ->first([
                'ei.id',
                'ei.employee_no',
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',
                'ep.tin_no',
                'ep.birthday',
                'ep.mobile_number',
                'ep.present_block',
                'ep.present_street',
                'ep.present_subdivision',
                'ep.present_barangay',
                'ep.present_city',
                'ep.present_province',
                'ep.present_zip',
                'ep.permanent_block',
                'ep.permanent_street',
                'ep.permanent_subdivision',
                'ep.permanent_barangay',
                'ep.permanent_city',
                'ep.permanent_province',
                'ep.permanent_zip',
                'p.name as position_name',
                'et.name as employment_type_name',
            ]);
    }

    private function normalizeAllowables(array $allowables): array
    {
        $normalized = [
            'sss' => 0.0,
            'gsis' => 0.0,
            'philhealth' => 0.0,
            'pagibig' => 0.0,
        ];

        foreach ($allowables as $item) {
            $name = strtolower((string) data_get($item, 'name', ''));
            $amount = (float) data_get($item, 'amount', 0);

            if (str_contains($name, 'sss')) {
                $normalized['sss'] += $amount;
                continue;
            }

            if (str_contains($name, 'gsis')) {
                $normalized['gsis'] += $amount;
                continue;
            }

            if (str_contains($name, 'philhealth')) {
                $normalized['philhealth'] += $amount;
                continue;
            }

            if (str_contains($name, 'pag-ibig') || str_contains($name, 'pagibig')) {
                $normalized['pagibig'] += $amount;
            }
        }

        return array_map(
            static fn ($amount) => round((float) $amount, 2),
            $normalized
        );
    }

    private function employerDetails(): array
    {
        $agency = DB::table('agency')->orderBy('id')->first();

        return [
            'name' => $agency->name ?? config('app.name'),
            'tin' => null,
            'address' => $agency->description ?? null,
            'rdo_code' => $agency->code ?? null,
        ];
    }

    private function formatEmployeeName(object $employee): string
    {
        $lastname = trim((string) ($employee->lastname ?? ''));
        $firstname = trim((string) ($employee->firstname ?? ''));
        $middlename = trim((string) ($employee->middlename ?? ''));
        $suffix = trim((string) ($employee->suffix ?? ''));
        $middleInitial = $middlename !== '' ? ' ' . strtoupper(substr($middlename, 0, 1)) . '.' : '';
        $suffixText = $suffix !== '' ? ' ' . $suffix : '';

        return trim(sprintf('%s, %s%s%s', $lastname, $firstname, $middleInitial, $suffixText), ', ');
    }

    private function combineAddress(array $parts): ?string
    {
        $address = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->implode(', ');

        return $address !== '' ? $address : null;
    }
}
