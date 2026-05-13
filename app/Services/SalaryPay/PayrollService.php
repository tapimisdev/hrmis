<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use App\Enums\PayrollStatusEnum;
use App\Jobs\Admin\Payroll\PayrollRegistryReport;
use App\Services\DailyTimeRecordService;
use App\Services\SalaryEmloyeeService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use \Carbon\Carbon;

use Throwable;

class PayrollService
{
    protected $daily_time_record_service;
    protected $salaryEmployeeService;
    private $date;
    private $cutoff;
    private $group_id;

    private $eligible;
    private $not_eligible;

    public function __construct(DailyTimeRecordService $daily_time_record_service, SalaryEmloyeeService $salaryEmployeeService)
    {
        $this->daily_time_record_service = $daily_time_record_service;
        $this->salaryEmployeeService = $salaryEmployeeService;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_salary as ps')
            ->leftJoin('employment_types as et', 'ps.employment_type_id', '=', 'et.id')
            ->select('ps.*', 'et.name as employment_name', 'et.code as employment_code');

        if (!empty($payload['employment_type'])) {
            $query->where('ps.employment_type_id', $payload['employment_type']);
        }
        if (!empty($payload['year'])) {
            $query->whereYear('ps.payroll_date', $payload['year']);
        }

        if (!empty($payload['month'])) {
            $query->whereMonth('ps.payroll_date', $payload['month']);
        }

        if (
            !empty($payload['cutoff'])
            && (string) ($payload['employment_type'] ?? '') !== EmploymentTypesEnum::REGULAR->value
        ) {
            $query->where('ps.cutoff', $payload['cutoff']);
        }

        if (!empty($payload['status'])) {
            $query->where('ps.status', $payload['status']);
        }

        return $query->get();
    }

    public function getEligibleEmployees($payload)
    {
        $this->date = $payload['date'] ?? null;
        $this->cutoff = $payload['cutoff'] ?? null;
        $this->group_id = $payload['group_id']; // can be 'custom'

        $latestOrg = DB::table('employee_organization as eo')
            ->select('eo.*')
            ->joinSub(
                DB::table('employee_organization')
                    ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
                    ->groupBy('employee_no'),
                'mx',
                function ($join) {
                    $join->on('eo.employee_no', '=', 'mx.employee_no')
                        ->on('eo.effectivity_date', '=', 'mx.max_effectivity_date');
                }
            )
            ->joinSub(
                DB::table('employee_organization')
                    ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
                    ->groupBy('employee_no', 'effectivity_date'),
                'mx2',
                function ($join) {
                    $join->on('eo.employee_no', '=', 'mx2.employee_no')
                        ->on('eo.effectivity_date', '=', 'mx2.effectivity_date')
                        ->on('eo.id', '=', 'mx2.max_id');
                }
            );

        $employees = DB::table('employee_information as ei')
            ->leftJoinSub($latestOrg, 'eo', function ($join) {
                $join->on('ei.employee_no', '=', 'eo.employee_no');
            })
            ->leftJoin('positions', 'eo.position_id', '=', 'positions.id')
            ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('eo.employment_type_id', $payload['employment_type_id'])
            ->select(
                'ep.firstname',
                'ep.middlename',
                'ep.lastname',
                'ep.suffix',

                'positions.name as position',
                'divisions.name as division',

                'eo.employment_type_id',

                'ei.user_id',
                'ei.employee_no',
                'ei.account_status'
            )
            ->get();


        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $emp) {
            $this->checkEligibility($emp);
        }

        $seperatedEmployee = [
            'eligible' => collect($this->eligible ?? [])
                ->sortBy('lastname')
                ->values()
                ->all(),

            'not_eligible' => collect($this->not_eligible ?? [])
                ->sortBy('lastname')
                ->values()
                ->all(),
        ];

        return $seperatedEmployee;
    }

    private function checkEligibility($employee)
    {
        // Prepare payload. Regular/plantilla payroll uses the whole month;
        // COS remains cutoff-based.
        $payload = array_merge($this->getPayrollRange($employee->employment_type_id), ['user_id' => $employee->user_id]);
        $dtr = $this->daily_time_record_service->getDTR($payload);

        // Extract summary counts in a clean, reusable way
        $summary = collect($dtr['summary'])->keyBy('label')->map(fn($item) => (int) ($item['value'] ?? 0));

        $incompleteLogs = $summary->get('Incomplete Logs', 0);
        $absentCount    = $summary->get('Absent', 0);
        $pendingLeave   = $summary->get('Pending Leave', 0);

        // Initialize remarks
        $remarks = [];
        $eligibleRemarks = [[
            'value' => $absentCount,
            'text' => "Absent: {$absentCount}",
            'url'  => null
        ]];

        if ($pendingLeave > 0) {
            $eligibleRemarks = [[
                'value' => $pendingLeave,
                'text' => "Leave/s: {$pendingLeave}",
                'url'  => null
            ]];
        }

        // Check account status
        if ($employee->account_status !== 'active') {
            $remarks[] = [
                'text' => 'This Employee is Inactive',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        $existence = $this->checkIfExistInPayroll($employee);

        if ($existence['is_exist']) {
            $remarks[] = [
                'text' => 'This employee is already included in Payroll No. '
                    . $existence['payroll']->payroll_no
                    . $this->formatExistingPayrollPeriod($existence['payroll']) . '.',

                'url' => route('salary-pay.show', [
                    'salary_pay' => $existence['payroll']->payroll_no,
                    'batch_id'   => $existence['payroll']->batch_id,
                ]),
            ];
        }

        if (!$this->hasWorkAndShift($employee->employee_no)) {
            $remarks[] = [
                'text' => 'This Employee has no work or shift schedule during this payroll date',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasInformation($employee->employee_no)) {
            $remarks[] = [
                'text' => 'Employee record is incomplete. Please verify account, personal, organizational, and position details.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (!$this->hasSalary($employee->employee_no)) {
            $remarks[] = [
                'text' => 'No valid salary record found for this employee as of the payroll date. Please update their salary details.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if (
            !$this->hasProject($employee->employee_no)
            && $employee->employment_type_id == EmploymentTypesEnum::COS->value
        ) {

            $eligibleRemarks[] = [
                'text' => 'COS employee has no assigned project during the payroll date. Please update.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        // Check incomplete logs
        // if ($incompleteLogs > 0) {
        //     $remarks[] = [
        //         'text' => sprintf(
        //             "This Employee %s %d missing log%s",
        //             $incompleteLogs === 1 ? 'has' : 'have',
        //             $incompleteLogs,
        //             $incompleteLogs === 1 ? '' : 's'
        //         ),
        //         'url' => route('daily-time-record.index', [
        //             'employee_no' => $employee->employee_no,
        //             'month' => \Carbon\Carbon::parse($this->date)->format('m'),
        //             'year' => \Carbon\Carbon::parse($this->date)->format('Y'),
        //         ]),
        //     ];
        // }

        // Determine eligibility
        $employee->remarks = $remarks ?: $eligibleRemarks;

        if (empty($remarks)) {

           $ingroup_exist = false;

            if ($this->group_id !== 'custom' && !empty($this->group_id)) {
                $ingroup_exist = DB::table('payroll_group_employees')
                    ->where('payroll_group_id', $this->group_id)
                    ->where('employee_no', $employee->employee_no)
                    ->exists();
            }

            $employee->selected = $ingroup_exist;
            $this->eligible[] = $employee;

        } else {
            $this->not_eligible[] = $employee;
        }
    }

    private function checkIfExistInPayroll($emp)
    {
        $date = Carbon::parse($this->date);
        $year = $date->year;
        $month = $date->month;

        $isExist = false;

        $payrolls = DB::table('payroll_salary')
            ->whereYear('payroll_date', $year)
            ->whereMonth('payroll_date', $month)
            ->where('status', '!=', 'cancelled')
            ->when(
                (string) $emp->employment_type_id !== EmploymentTypesEnum::REGULAR->value,
                fn ($query) => $query->where('cutoff', $this->cutoff)
            )
            ->get();


        $db_name = 'payroll_salary_permanent_employees';

        if ($emp->employment_type_id == EmploymentTypesEnum::COS->value) {
            $db_name = 'payroll_salary_employee';
        }

        foreach ($payrolls as $payroll) {
            $exists = DB::table($db_name)
                ->where('payroll_salary_id', $payroll->id)
                ->where('employee_no', $emp->employee_no)
                ->exists();

            if ($exists) {
                $isExist = true;
                $payroll = $payroll;
                break;
            }
        }

        return [
            'is_exist' => $isExist,
            'payroll' => $payroll ?? []
        ];
    }

    private function hasWorkAndShift($emp_no)
    {
        $schedule = $this->salaryEmployeeService
            ->activeShift($emp_no, $this->date)
            ->leftJoin('shifts as s', 'sw1.shift_id', '=', 's.id')
            ->select(
                'sw1.id'
            )
            ->first();

        return $schedule ? true : false;
    }

    private function hasInformation($emp_no)
    {
        $info = DB::table('employee_organization')
            ->leftJoin('employee_information', 'employee_organization.employee_no', '=', 'employee_information.employee_no')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
            ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
            ->where('employee_organization.employee_no', $emp_no)
            ->select('employee_information.id as employee_information_id', 'employee_personal.id as employee_personal_id', 'positions.id as positions_id', 'users.id as users_id')
            ->first();

        Log::info('------------------ INFOR -------------------------');
        Log::info('INFO DATA:', (array) $info);

        // Make sure all critical relationships exist
        return $info && $info->employee_information_id && $info->employee_personal_id && $info->positions_id && $info->users_id;
    }

    private function hasSalary($emp_no)
    {
        $employee_salary = $this->salaryEmployeeService
            ->activeSalary($emp_no, $this->date)
            ->first();

        return !is_null($employee_salary);
    }

    private function hasProject($emp_no)
    {

        $date = $this->date;

        $projects_employee = DB::table('employee_projects')
            ->where('employee_no', $emp_no)
            ->whereDate('start_date', '<=', $this->date)
            ->where(function ($query) use ($date) {
                $query->whereDate('end_date', '>=', $date)
                    ->orWhereNull('end_date');
            })
            ->orderByDesc('start_date')
            ->first();

        Log::info("Emp: " . $emp_no);
        Log::info("BOOL: " . !is_null($projects_employee));
        Log::info("Project: " . print_r($projects_employee, true));

        if (!is_null($projects_employee)) {
            return true;
        }

        return false;
    }

    public function generatePayrollRegistryReport($payload, $payroll_id)
    {
        $eligibleEmployees = collect($payload['employees']['eligible'])->where('selected', true);

        if (empty($eligibleEmployees)) {
            Log::warning("No eligible employees found for payroll ID: {$payroll_id}");
            return null;
        }

        $batch = Bus::batch([])
            ->then(function (Batch $batch) {
                // $admin = \App\Models\User::role('admin')->first();
                // if ($admin) {
                //     $admin->notify(new \App\Notifications\PayrollBatchCompleted($batch, 'success'));
                // } else {
                //     Log::warning('Admin not found while notifying payroll batch success.');
                // }
            })
            ->catch(function (Batch $batch, \Throwable $e) {
                // $admin = \App\Models\User::role('admin')->first();
                // if ($admin) {
                //     $admin->notify(new \App\Notifications\PayrollBatchCompleted($batch, 'failed', $e));
                // } else {
                //     Log::error('Admin not found while notifying payroll batch failure.');
                // }
                // Log::error("Payroll batch failed: {$e->getMessage()}");
            })
            ->name("Payroll Registry Report #{$payroll_id}")
            ->dispatch();

        DB::table('payroll_salary')
            ->where('id', $payroll_id)
            ->update(['batch_id' => $batch->id]);

        $user = Auth::user();

        foreach ($eligibleEmployees as $employee) {
            $batch->add(new PayrollRegistryReport($employee, $payroll_id, $user->id, $user->name));
        }

        return $batch->id;
    }

    public function createPayroll($payload)
    {
        $payroll_no = generateNo('SL-', 4);
        $isRegular = (string) $payload['employment_type_id'] === EmploymentTypesEnum::REGULAR->value;
        $isCos = (string) $payload['employment_type_id'] === EmploymentTypesEnum::COS->value;
        $applyDeduction = !$isCos || ($payload['apply_deduction'] ?? 'yes') !== 'no';
        $payrollDate = Carbon::parse($payload['date']);
        $cutoff = $isRegular ? 'second_cutoff' : $payload['cutoff'];
        $applyOptions = $isCos ? $this->normalizeDeductionOptions($payload['deduction_apply_options'] ?? []) : [];
        $deferredDeduction = ['cutoff' => null, 'date' => null];
        $periodCovered = $isRegular
            ? $payrollDate->format('F Y')
            : $payrollDate->format('F Y') . ' ' .
                ($payload['cutoff'] === 'first_cutoff' ? '1-15' : '16-' . $payrollDate->copy()->endOfMonth()->format('d'));

        // Insert payroll and get its ID
        $payroll_id = DB::table('payroll_salary')->insertGetId([
            'label' => $payload['label'],
            'payroll_no' => $payroll_no,

            'period_covered' => $periodCovered,

            'no_employee' => 0,
            'gross_amount' => 0,
            'deduction_amount' => 0,
            'netpay_amount' => 0,
            'processed_by_id' => auth('sanctum')->user()->id,
            'payroll_date' => $payload['date'],
            'cutoff' => $cutoff,
            'employment_type_id' => $payload['employment_type_id'],
            'is_aut_deducted' => false,
            'apply_deduction' => $applyDeduction,
            'deduction_deferred_cutoff' => $deferredDeduction['cutoff'],
            'deduction_deferred_date' => $deferredDeduction['date'],
            'deduction_apply_options' => $isCos ? json_encode($applyOptions) : null,
            'deduction_defer_option' => (!$applyDeduction && $isCos)
                ? 'tbd'
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($isCos && $applyDeduction) {
            $selectedDeferredPayrollIds = $this->selectedDeferredPayrollIds($applyOptions);

            if (!empty($selectedDeferredPayrollIds)) {
                DB::table('payroll_salary')
                    ->whereIn('id', $selectedDeferredPayrollIds)
                    ->where('employment_type_id', EmploymentTypesEnum::COS->value)
                    ->where('apply_deduction', false)
                    ->whereNull('deduction_applied_payroll_id')
                    ->whereIn('status', $this->deductibleCosPayrollStatuses())
                    ->update([
                        'deduction_applied_payroll_id' => $payroll_id,
                        'updated_at' => now(),
                    ]);
            }
        }

        // Insert approvers for this payroll
        collect($payload['approved_by'])
            ->flatMap(function ($approvers, $level) use ($payroll_id) {
                return collect($approvers)->map(function ($user_id) use ($payroll_id, $level) {
                    return [
                        'payroll_salary_id' => $payroll_id,
                        'user_id' => $user_id,
                        'level' => (int) $level,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
            })
            ->pipe(function ($records) {
                DB::table('payroll_salary_approvers')->insert($records->toArray());
            });

        // Return inserted payroll ID or record
        return [
            'payroll_no' => $payroll_no,
            'payroll_id' => $payroll_id,
        ];
    }

    private function getNextCutoffDeductionSchedule(Carbon $payrollDate, string $cutoff): array
    {
        if ($cutoff === 'second_cutoff') {
            return [
                'cutoff' => 'first_cutoff',
                'date' => $payrollDate->copy()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            ];
        }

        return [
            'cutoff' => 'second_cutoff',
            'date' => $payrollDate->copy()->day(16)->format('Y-m-d'),
        ];
    }

    public function getCosDeductionSchedulePreview(array $payload): array
    {
        $isCos = (string) ($payload['employment_type_id'] ?? '') === EmploymentTypesEnum::COS->value;

        if (!$isCos || empty($payload['date']) || empty($payload['cutoff'])) {
            return [
                'incoming' => [],
                'incoming_count' => 0,
                'current_applies' => false,
                'current_deferred' => null,
                'applied_cutoff_count' => 0,
            ];
        }

        $payrollDate = Carbon::parse($payload['date']);
        $cutoff = $payload['cutoff'];
        $applyDeduction = ($payload['apply_deduction'] ?? 'yes') !== 'no';
        $applyOptions = $this->normalizeDeductionOptions($payload['deduction_apply_options'] ?? []);
        $selectedDeferredPayrollIds = $this->selectedDeferredPayrollIds($applyOptions);
        $currentApplies = $applyDeduction && (empty($applyOptions) || in_array('current', $applyOptions, true));
        $incomingPayrolls = $this->getPendingCosDeferredPayrolls($payrollDate, $cutoff);

        if ($applyDeduction && !empty($selectedDeferredPayrollIds)) {
            $incomingPayrolls = $incomingPayrolls->whereIn('id', $selectedDeferredPayrollIds)->values();
        } elseif ($applyDeduction) {
            $incomingPayrolls = collect([]);
        }

        $incoming = $incomingPayrolls
            ->map(fn ($payroll) => [
                'id' => $payroll->id,
                'label' => $payroll->label,
                'payroll_no' => $payroll->payroll_no,
                'period_covered' => $payroll->period_covered,
                'payroll_date' => $payroll->payroll_date,
                'cutoff' => $payroll->cutoff,
                'status' => $payroll->status,
                'url' => route('salary-pay.show', ['salary_pay' => $payroll->payroll_no]),
            ])
            ->values()
            ->all();

        return [
            'incoming' => $incoming,
            'incoming_count' => count($incoming),
            'current_applies' => $currentApplies,
            'current_deferred' => null,
            'applied_cutoff_count' => count($incoming) + ($currentApplies ? 1 : 0),
        ];
    }

    public function getCosDeductionOptions(array $payload): array
    {
        if (empty($payload['date']) || empty($payload['cutoff'])) {
            return [
                'apply_options' => [],
                'defer_options' => [],
            ];
        }

        $payrollDate = Carbon::parse($payload['date']);
        $cutoff = $payload['cutoff'];

        $applyOptions = [[
            'value' => 'current',
            'label' => 'Current Deductions',
            'description' => 'Apply this payroll\'s AUT, tax, overall tax, and HMO deductions.',
        ]];

        foreach ($this->getPendingCosDeferredPayrolls($payrollDate, $cutoff) as $payroll) {
            $description = trim(($payroll->period_covered ?? '') . ($payroll->label ? ' - ' . $payroll->label : ''));

            $applyOptions[] = [
                'value' => 'payroll:' . $payroll->id,
                'label' => $payroll->payroll_no,
                'description' => ($description ? $description . ' - ' : '')
                    . 'Deferred AUT, tax, overall tax, and HMO deductions.',
            ];
        }

        return [
            'apply_options' => $applyOptions,
            'defer_options' => [
                [
                    'value' => 'tbd',
                    'label' => 'To be determined',
                    'description' => 'Apply this deduction on the next desired cutoff.',
                ],
            ],
        ];
    }

    private function getPendingCosDeferredPayrolls(Carbon $payrollDate, string $cutoff)
    {
        return DB::table('payroll_salary')
            ->where('employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('apply_deduction', false)
            ->where(function ($query) use ($payrollDate) {
                $query->where(function ($scheduled) use ($payrollDate) {
                    $scheduled
                        ->whereNotNull('deduction_deferred_date')
                        ->whereDate('deduction_deferred_date', '<=', $payrollDate->format('Y-m-d'));
                })
                    ->orWhere(function ($undetermined) use ($payrollDate) {
                        $undetermined
                            ->where('deduction_defer_option', 'tbd')
                            ->whereDate('payroll_date', '<=', $payrollDate->format('Y-m-d'));
                    });
            })
            ->whereNull('deduction_applied_payroll_id')
            ->whereIn('status', $this->deductibleCosPayrollStatuses())
            ->orderBy('payroll_date')
            ->orderBy('id')
            ->get([
                'id',
                'label',
                'payroll_no',
                'period_covered',
                'payroll_date',
                'cutoff',
                'deduction_deferred_cutoff',
                'deduction_deferred_date',
                'status',
            ]);
    }

    private function deductibleCosPayrollStatuses(): array
    {
        return [
            PayrollStatusEnum::Approved->value,
            PayrollStatusEnum::ForReleasing->value,
            PayrollStatusEnum::Completed->value,
        ];
    }

    private function normalizeDeductionOptions(array|string|null $options): array
    {
        if (is_string($options)) {
            $options = [$options];
        }

        return collect($options ?? [])
            ->filter(fn ($option) => is_string($option) && $option !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function parseDeductionOptions(null|string|array $options): array
    {
        if (is_array($options)) {
            return $options;
        }

        if (!is_string($options) || $options === '') {
            return [];
        }

        $decoded = json_decode($options, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function selectedDeferredPayrollIds(array $options): array
    {
        return collect($options)
            ->filter(fn ($option) => str_starts_with($option, 'payroll:'))
            ->map(fn ($option) => (int) substr($option, strlen('payroll:')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function getCutoff()
    {
        return $this->getPayrollRange(null);
    }

    private function getPayrollRange($employmentTypeId = null)
    {
        $dateObj = new \DateTime($this->date);
        $year  = $dateObj->format('Y');
        $month = $dateObj->format('m');

        if ((string) $employmentTypeId === EmploymentTypesEnum::REGULAR->value) {
            $start = "$year-$month-01";
            $end   = $dateObj->format('Y-m-t');
        } elseif ($this->cutoff === 'first_cutoff') {
            $start = "$year-$month-01";
            $end   = "$year-$month-15";
        } elseif ($this->cutoff === 'second_cutoff') {
            $start = "$year-$month-16";
            $end   = $dateObj->format('Y-m-t'); // last day of month
        } else {
            throw new \InvalidArgumentException("Invalid cutoff type: $this->cutoff");
        }

        return [
            'startDate' => $start,
            'endDate'   => $end,
        ];
    }

    private function formatExistingPayrollPeriod(object $payroll): string
    {
        if ((string) $payroll->employment_type_id === EmploymentTypesEnum::REGULAR->value) {
            return ' for ' . Carbon::parse($payroll->payroll_date)->format('F Y');
        }

        return ' for '
            . Carbon::parse($payroll->payroll_date)->format('F')
            . ' ( '
            . $payroll->cutoff
            . ' )';
    }

    public function getHolidays($payload)
    {
        $start_date = $payload['start_date'];
        $end_date = $payload['end_date'];

        $holidays = DB::table('holidays')
            ->where(function ($query) use ($start_date, $end_date) {
                $query
                    // For normal (non-repeating) holidays, use full date range
                    ->where(function ($q) use ($start_date, $end_date) {
                        $q->where('is_repeating', false)
                            ->whereBetween('date', [$start_date, $end_date]);
                    })
                    // For repeating holidays, match only month and day
                    ->orWhere(function ($q) use ($start_date, $end_date) {
                        $startMonthDay = date('m-d', strtotime($start_date));
                        $endMonthDay = date('m-d', strtotime($end_date));

                        $q->where('is_repeating', true)
                            ->whereRaw("DATE_FORMAT(date, '%m-%d') BETWEEN ? AND ?", [$startMonthDay, $endMonthDay]);
                    });
            })
            ->where('is_active', true)
            ->get()
            ->map(function ($holiday) use ($start_date) {
                $date = $holiday->is_repeating
                    ? date('Y', strtotime($start_date)) . '-' . date('m-d', strtotime($holiday->date))
                    : $holiday->date;

                return [
                    'id' => $holiday->id,
                    'title' => ucfirst(str_replace('_', ' ', $holiday->name)),
                    'start' => $date,
                    'allDay' => true,
                    'backgroundColor' => '#008046ff',
                    'borderColor' => '#008046ff',
                    'className' => 'text-white text-center text-shadow-lg d-flex justify-content-center align-items-center h-100 w-100',
                    'extendedProps' => [
                        'id' => $holiday->id,
                        'category' => 'holiday',
                        'type' => $holiday->type,
                        'is_repeating' => (bool) $holiday->is_repeating,
                        'no_work_rate' => $holiday->no_work_rate,
                        'work_rate' => $holiday->work_rate,
                        'overtime_rate' => $holiday->overtime_rate,
                    ],
                ];
            });

        return $holidays;
    }

    public function getSuspensions($payload)
    {
        $start_date = $payload['start_date'];
        $end_date = $payload['end_date'];

        $suspensions = DB::table('suspension_dates')
            ->leftJoin('suspension', 'suspension_dates.suspension_id', '=', 'suspension.id')
            ->select('suspension_dates.*', 'suspension.name', 'suspension.description')
            ->whereBetween('suspension_dates.date', [$start_date, $end_date])
            ->where('suspension_dates.isActive', true)
            ->get()
            ->map(function ($suspension) {
                $title = ucfirst(str_replace('_', ' ', $suspension->name));

                $color = match ($suspension->type) {
                    'whole_day' => '#c0392b', // red for full day
                    'half_day' => '#f39c12',  // orange for half day
                    default => '#7f8c8d',     // gray for unknown type
                };

                $desc = $suspension->description
                    ? $suspension->description
                    : ucfirst($suspension->type) . ' suspension';

                return [
                    'id' => $suspension->suspension_id,
                    'title' => $title,
                    'start' => $suspension->date,
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'className' => 'text-white text-center text-shadow-lg d-flex justify-content-center align-items-center h-100 w-100',
                    'extendedProps' => [
                        'id' => $suspension->id,
                        'suspension_id' => $suspension->suspension_id,
                        'category' => 'suspension',
                        'type' => $suspension->type,
                        'shift' => $suspension->shift,
                        'description' => $desc,
                    ],
                ];
            });

        return $suspensions;
    }

    public function getPayrollRegistry(object $payroll, string $payroll_id, bool $isGrouped = true)
    {
        $employment_type_id = $payroll->employment_type_id;

        // Get payroll date
        $payroll_date = DB::table('payroll_salary')
            ->where('id', $payroll_id)
            ->value('payroll_date');

        /* ===========================
        |  Get payroll employees
        ===========================*/
        if ($employment_type_id == EmploymentTypesEnum::COS->value) {

            $pse = DB::table('payroll_salary_employee as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->where('payroll_salary_id', $payroll_id)
                ->select('pse.*', 'ps.payroll_no', 'ps.payroll_date', 'ps.cutoff', 'ps.period_covered', 'ps.apply_deduction', 'ps.deduction_apply_options')
                ->get();
        } else { // REGULAR
            $latestOrgDate = DB::table('employee_organization')
                ->selectRaw('employee_no, MAX(effectivity_date) as max_effectivity_date')
                ->when($payroll_date, fn ($query) => $query->whereDate('effectivity_date', '<=', $payroll_date))
                ->groupBy('employee_no');

            $latestOrgId = DB::table('employee_organization')
                ->selectRaw('employee_no, effectivity_date, MAX(id) as max_id')
                ->groupBy('employee_no', 'effectivity_date');

            $pse = DB::table('payroll_salary_permanent_employees as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->leftJoinSub($latestOrgDate, 'latest_org_date', function ($join) {
                    $join->on('pse.employee_no', '=', 'latest_org_date.employee_no');
                })
                ->leftJoinSub($latestOrgId, 'latest_org_id', function ($join) {
                    $join->on('latest_org_date.employee_no', '=', 'latest_org_id.employee_no')
                        ->on('latest_org_date.max_effectivity_date', '=', 'latest_org_id.effectivity_date');
                })
                ->leftJoin('employee_organization as eo', 'latest_org_id.max_id', '=', 'eo.id')
                ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
                ->where('payroll_salary_id', $payroll_id)
                ->select(
                    'pse.*',
                    'ps.payroll_date',
                    'ps.cutoff',
                    'ps.period_covered',
                    'divisions.id as division_id',
                    'divisions.name as division_name'
                )
                ->get();
        }

        /* ===========================
        |  Enrich employees
        ===========================*/
        $enriched = $pse->map(function ($d) use ($employment_type_id, $payroll_date, $payroll) {

            $deductions = [];
            $earnings   = [];

            if ($employment_type_id == EmploymentTypesEnum::REGULAR->value) {
                $deductions = DB::table('payroll_salary_permanents_employee_deductions')
                    ->where('pspe_id', $d->id)
                    ->get();
                $earnings = DB::table('payroll_salary_employee_earnings')
                    ->where('payroll_se_id', $d->id)
                    ->get();
            }

            // COS → project
            $project = DB::table('employee_projects as ep')
                ->join('projects as p', 'ep.project_id', '=', 'p.id')
                ->where('employee_no', $d->employee_no)
                ->whereDate('ep.start_date', '<=', $payroll_date)
                ->where(function ($q) use ($payroll_date) {
                    $q->whereDate('ep.end_date', '>=', $payroll_date)
                        ->orWhereNull('ep.end_date');
                })
                ->orderByDesc('ep.start_date')
                ->select('p.id', 'p.name')
                ->first();

            // ----------------------------
            // Determine pay periods.
            // COS is cutoff-based; regular/plantilla is monthly.
            // ----------------------------
            $cut_offs = [];

            if ($employment_type_id == EmploymentTypesEnum::REGULAR->value) {
                [$netSalary15th, $netSalary30th] = $this->splitRegularNetPay((float) ($d->net_pay ?? 0));

                $cut_offs[] = [
                    'period' => $d->period_covered ?? Carbon::parse($payroll_date)->format('F Y'),
                    'alias'  => '15th',
                    'amount' => $netSalary15th,
                ];

                $cut_offs[] = [
                    'period' => $d->period_covered ?? Carbon::parse($payroll_date)->format('F Y'),
                    'alias'  => '30th',
                    'amount' => $netSalary30th,
                ];
            } elseif (!empty($d->period_covered)) {
                preg_match('/^([A-Za-z]+)\s+(\d{4})\s+(\d+-\d+)$/', $d->period_covered, $matches);

                if ($matches) {
                    [, $month, $year, $currentPeriod] = $matches;

                    $ordinal = function (int $number) {
                        if (($number % 100) >= 11 && ($number % 100) <= 13) return $number . 'th';
                        return match ($number % 10) {
                            1 => $number . 'st',
                            2 => $number . 'nd',
                            3 => $number . 'rd',
                            default => $number . 'th',
                        };
                    };

                    $firstPeriod = null;
                    $firstAlias  = null;
                    $firstAmount = 0.0;

                    // Check if current cutoff is the second cutoff
                    if (($d->cutoff ?? '') === 'second_cutoff') {
                        $firstCutoff = DB::table('payroll_salary')
                            ->where('cutoff', 'first_cutoff')
                            ->where('period_covered', 'like', "{$month} {$year}%")
                            ->first();

                        if ($firstCutoff) {
                            preg_match('/(\d+-\d+)$/', $firstCutoff->period_covered, $fcMatch);
                            $firstPeriod = $fcMatch[1] ?? null;

                            if ($firstPeriod) {
                                [, $firstEnd] = explode('-', $firstPeriod);
                                $firstAlias = $ordinal((int) $firstEnd);
                            }

                            $firstEmployee = DB::table('payroll_salary_employee')
                                ->where('payroll_salary_id', $firstCutoff->id)
                                ->where('employee_no', $d->employee_no)
                                ->first();

                            if ($firstEmployee) {
                                $firstAmount = (float) ($firstEmployee->net_pay ?? 0);
                            }
                        }

                        // Place the second cutoff first in array
                        $cut_offs[] = [
                            'period' => $currentPeriod,
                            'alias'  => $ordinal((int) explode('-', $currentPeriod)[1]),
                            'amount' => (float) ($d->net_pay ?? 0),
                        ];

                        if ($firstPeriod) {
                            $cut_offs[] = [
                                'period' => $firstPeriod,
                                'alias'  => $firstAlias,
                                'amount' => $firstAmount,
                            ];
                        }

                    } else {
                        // Normal case: first cutoff
                        [, $currentEnd] = explode('-', $currentPeriod);
                        $currentAlias = $ordinal((int) $currentEnd);

                        $cut_offs[] = [
                            'period' => $currentPeriod,
                            'alias'  => $currentAlias,
                            'amount' => (float) ($d->net_pay ?? 0),
                        ];

                    }
                }
            }

            $appliedAut = 0;
            $autBreakdown = [];
            $deductionBreakdowns = [
                'aut' => [],
                'ewt_2' => [],
                'percentage_tax_3' => [],
                'tax_ewt_5' => [],
                'w_tax' => [],
                'hmo' => [],
            ];

            if ($employment_type_id == EmploymentTypesEnum::COS->value && (bool) ($d->apply_deduction ?? true)) {
                $deductionOptions = $this->parseDeductionOptions($d->deduction_apply_options ?? null);
                $hasExplicitDeductionOptions = !empty($deductionOptions);
                $applyCurrentDeduction = !$hasExplicitDeductionOptions || in_array('current', $deductionOptions, true);
                $selectedDeferredPayrollIds = $hasExplicitDeductionOptions
                    ? $this->selectedDeferredPayrollIds($deductionOptions)
                    : null;

                $currentAut = (float) ($d->ut ?? 0) + (float) ($d->absences ?? 0);
                if ($applyCurrentDeduction && $currentAut > 0) {
                    $autBreakdown[] = [
                        'label' => 'Current payroll',
                        'period_covered' => $d->period_covered ?? null,
                        'payroll_no' => $d->payroll_no ?? ($payroll->payroll_no ?? null),
                        'url' => ($d->payroll_no ?? ($payroll->payroll_no ?? null))
                            ? route('salary-pay.show', ['salary_pay' => $d->payroll_no ?? $payroll->payroll_no])
                            : null,
                        'amount' => round($currentAut, 2),
                    ];
                }

                $deferredAutBreakdown = $this->getDeferredCosAutBreakdown(
                    $d->employee_no,
                    $d->payroll_date,
                    $d->cutoff,
                    $selectedDeferredPayrollIds
                );

                $autBreakdown = array_merge($autBreakdown, $deferredAutBreakdown);
                $appliedAut = collect($autBreakdown)->sum('amount');

                $deductionBreakdowns = $this->getCosDeductionBreakdowns(
                    $d,
                    $payroll,
                    $applyCurrentDeduction,
                    $selectedDeferredPayrollIds
                );
                $deductionBreakdowns['aut'] = $autBreakdown;
            }

            return [
                'employee_no' => $d->employee_no,
                'name'        => $d->name,
                'position'    => $d->position,
                'monthly_rate' => $d->monthly_rate,
                'salary_grade' => $d->salary_grade ?? null,
                'basic_pay'   => $d->basic_pay ?? null,
                'aut'         => $appliedAut,
                'ut'          => $employment_type_id == EmploymentTypesEnum::REGULAR->value ? 0 : $d->ut,
                'absences'    => $employment_type_id == EmploymentTypesEnum::REGULAR->value ? 0 : $d->absences,
                'overtime'    => $d->overtime,
                'holiday'     => $d->holiday,
                'gross_pay'   => $d->gross_pay ?? null,
                'total_deductions' => $d->total_deductions ?? null,
                'net_pay'     => $d->net_pay ?? 0,
                'net_salary_15th' => $netSalary15th ?? null,
                'net_salary_30th' => $netSalary30th ?? null,
                'salary_adjustment' => $d->salary_adjustment,
                'remarks'     => $d->remarks ?? null,
                'deductions'  => $deductions,
                'earnings'    => $earnings,
                'ewt_2'       => $d->ewt_2 ?? null,
                'percentage_tax_3' => $d->percentage_tax_3 ?? null,
                'tax_ewt_5'   => $d->tax_ewt_5 ?? null,
                'w_tax'       => $d->w_tax ?? null,
                'cut_offs'    => $cut_offs,
                'hmo'         => $d->hmo ?? null,
                'project_id'   => $project->id   ?? null,
                'project_name' => $project->name ?? 'No Project',
                'division_id'  => $d->division_id ?? null,
                'division_name' => $d->division_name ?? 'No Division',
                'applied_aut'  => $appliedAut,
                'aut_breakdown' => $autBreakdown,
                'deduction_breakdowns' => $deductionBreakdowns,
            ];
        });

        /* ===========================
        |  GROUPING
        ===========================*/
        if (!$isGrouped) {
            return response()->json($enriched);
        }

        $grouped = [];

        foreach ($enriched as $emp) {

            $groupId   = ($employment_type_id == EmploymentTypesEnum::COS->value)
                ? ($emp['project_id'] ?? 'others')
                : ($emp['division_id'] ?? 'others');

            $groupName = ($employment_type_id == EmploymentTypesEnum::COS->value)
                ? $emp['project_name']
                : $emp['division_name'];

            if (!isset($grouped[$groupId])) {
                $grouped[$groupId] = [
                    'name' => $groupName,
                    'employees' => []
                ];
            }

            $employeePayload = [
                'aut'          => $employment_type_id == EmploymentTypesEnum::COS->value ? $emp['applied_aut'] : 0,
                'aut_breakdown' => $employment_type_id == EmploymentTypesEnum::COS->value ? $emp['aut_breakdown'] : [],
                'employee_no'  => $emp['employee_no'],
                'name'         => $emp['name'],
                'position'     => $emp['position'],
                'monthly_rate' => $emp['monthly_rate'],
                'salary_earned' => $emp['basic_pay'],
                'ut'           => $emp['ut'],
                'absences'     => $emp['absences'],
                'overtime'     => $emp['overtime'],
                'holiday'      => $emp['holiday'],
                'total_salary' => $employment_type_id == EmploymentTypesEnum::COS->value
                    ? $emp['gross_pay'] - $emp['applied_aut']
                    : $emp['gross_pay'],
                'deductions'   => $emp['deductions'],
                'earnings'     => $emp['earnings'],
                'adjustment'   => $emp['salary_adjustment'],
                'ewt_2'       => $emp['ewt_2'] ?? null,
                'percentage_tax_3' => $emp['percentage_tax_3'] ?? null,
                'tax_ewt_5'   => $emp['tax_ewt_5'] ?? null,
                'w_tax'       => $emp['w_tax'] ?? null,
                'hmo'         => $emp['hmo'] ?? null,
                'deduction_breakdowns' => $employment_type_id == EmploymentTypesEnum::COS->value
                    ? $emp['deduction_breakdowns']
                    : [],
                'net_salary'   => $emp['net_pay'],
                'cut_offs'     => $emp['cut_offs'],
                'remarks'      => $emp['remarks'],
            ];

            $grouped[$groupId]['employees'][] = $employeePayload;
        }

        return response()->json(array_values($grouped));
    }

    private function getDeferredCosAutBreakdown(string $employeeNo, ?string $payrollDate, ?string $cutoff, ?array $selectedPayrollIds = null): array
    {
        if (empty($payrollDate) || empty($cutoff)) {
            return [];
        }

        $date = Carbon::parse($payrollDate);

        return DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $employeeNo)
            ->where('ps.employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('ps.apply_deduction', false)
            ->when(
                is_array($selectedPayrollIds),
                fn ($query) => $query->whereIn('ps.id', $selectedPayrollIds ?: [0]),
                fn ($query) => $query
                    ->where('ps.deduction_deferred_cutoff', $cutoff)
                    ->whereYear('ps.deduction_deferred_date', $date->year)
                    ->whereMonth('ps.deduction_deferred_date', $date->month)
                    ->whereNull('ps.deduction_applied_payroll_id')
            )
            ->whereIn('ps.status', $this->deductibleCosPayrollStatuses())
            ->orderBy('ps.payroll_date')
            ->orderBy('ps.id')
            ->select(
                'ps.payroll_no',
                'ps.period_covered',
                'ps.payroll_date',
                'ps.cutoff',
                DB::raw('(COALESCE(pse.ut, 0) + COALESCE(pse.absences, 0)) as amount')
            )
            ->get()
            ->filter(fn ($row) => (float) $row->amount > 0)
            ->map(fn ($row) => [
                'label' => 'Deferred payroll',
                'period_covered' => $row->period_covered,
                'payroll_no' => $row->payroll_no,
                'payroll_date' => $row->payroll_date,
                'cutoff' => $row->cutoff,
                'url' => route('salary-pay.show', ['salary_pay' => $row->payroll_no]),
                'amount' => round((float) $row->amount, 2),
            ])
            ->values()
            ->all();
    }

    private function getCosDeductionBreakdowns(
        object $employeePayroll,
        object $payroll,
        bool $applyCurrentDeduction,
        ?array $selectedPayrollIds = null
    ): array {
        $keys = ['ewt_2', 'percentage_tax_3', 'tax_ewt_5', 'w_tax', 'hmo'];
        $breakdowns = [
            'aut' => [],
            'ewt_2' => [],
            'percentage_tax_3' => [],
            'tax_ewt_5' => [],
            'w_tax' => [],
            'hmo' => [],
        ];

        $deferred = $this->getDeferredCosTaxBreakdowns(
            $employeePayroll->employee_no,
            $employeePayroll->payroll_date,
            $employeePayroll->cutoff,
            $selectedPayrollIds
        );

        foreach ($keys as $key) {
            $deferredTotal = collect($deferred[$key] ?? [])->sum('amount');
            $currentAmount = round(max(0, (float) ($employeePayroll->{$key} ?? 0) - $deferredTotal), 2);

            if ($applyCurrentDeduction && $currentAmount > 0) {
                $breakdowns[$key][] = [
                    'label' => 'Current payroll',
                    'period_covered' => $employeePayroll->period_covered ?? null,
                    'payroll_no' => $employeePayroll->payroll_no ?? ($payroll->payroll_no ?? null),
                    'payroll_date' => $employeePayroll->payroll_date ?? null,
                    'cutoff' => $employeePayroll->cutoff ?? null,
                    'amount' => $currentAmount,
                ];
            }

            $breakdowns[$key] = array_merge($breakdowns[$key], $deferred[$key] ?? []);
        }

        return $breakdowns;
    }

    private function getDeferredCosTaxBreakdowns(string $employeeNo, ?string $payrollDate, ?string $cutoff, ?array $selectedPayrollIds = null): array
    {
        $breakdowns = [
            'ewt_2' => [],
            'percentage_tax_3' => [],
            'tax_ewt_5' => [],
            'w_tax' => [],
            'hmo' => [],
        ];

        if (empty($payrollDate) || empty($cutoff)) {
            return $breakdowns;
        }

        $date = Carbon::parse($payrollDate);
        $taxFlags = $this->getCosTaxFlags($employeeNo);

        $rows = DB::table('payroll_salary as ps')
            ->join('payroll_salary_employee as pse', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('pse.employee_no', $employeeNo)
            ->where('ps.employment_type_id', EmploymentTypesEnum::COS->value)
            ->where('ps.apply_deduction', false)
            ->when(
                is_array($selectedPayrollIds),
                fn ($query) => $query->whereIn('ps.id', $selectedPayrollIds ?: [0]),
                fn ($query) => $query
                    ->where('ps.deduction_deferred_cutoff', $cutoff)
                    ->whereYear('ps.deduction_deferred_date', $date->year)
                    ->whereMonth('ps.deduction_deferred_date', $date->month)
                    ->whereNull('ps.deduction_applied_payroll_id')
            )
            ->whereIn('ps.status', $this->deductibleCosPayrollStatuses())
            ->orderBy('ps.payroll_date')
            ->orderBy('ps.id')
            ->select(
                'ps.payroll_no',
                'ps.period_covered',
                'ps.payroll_date',
                'ps.cutoff',
                'pse.gross_pay'
            )
            ->get();

        foreach ($rows as $row) {
            $gross = (float) ($row->gross_pay ?? 0);
            $amounts = [
                'ewt_2' => $taxFlags['two_percent'] ? round(max(0, $gross - 10417) * 0.02, 2) : 0,
                'percentage_tax_3' => $taxFlags['three_percent'] ? round($gross * 0.03, 2) : 0,
                'tax_ewt_5' => $taxFlags['five_percent'] ? round($gross * 0.05, 2) : 0,
                'hmo' => $this->getCosHmoAmount($employeeNo, $row->payroll_date, $row->cutoff),
            ];
            $amounts['w_tax'] = round($amounts['ewt_2'] + $amounts['percentage_tax_3'] + $amounts['tax_ewt_5'], 2);

            foreach ($amounts as $key => $amount) {
                if ($amount <= 0) {
                    continue;
                }

                $breakdowns[$key][] = [
                    'label' => 'Deferred payroll',
                    'period_covered' => $row->period_covered,
                    'payroll_no' => $row->payroll_no,
                    'payroll_date' => $row->payroll_date,
                    'cutoff' => $row->cutoff,
                    'amount' => $amount,
                ];
            }
        }

        return $breakdowns;
    }

    private function getCosTaxFlags(string $employeeNo): array
    {
        $flags = DB::table('employee_information')
            ->where('employee_no', $employeeNo)
            ->select('two_percent', 'three_percent', 'five_percent')
            ->first();

        return [
            'two_percent' => (bool) ($flags->two_percent ?? false),
            'three_percent' => (bool) ($flags->three_percent ?? false),
            'five_percent' => (bool) ($flags->five_percent ?? false),
        ];
    }

    private function getCosHmoAmount(string $employeeNo, ?string $payrollDate, ?string $cutoff): float
    {
        if (empty($payrollDate) || empty($cutoff)) {
            return 0;
        }

        $deductionApplied = DB::table('employee_salary')
            ->where('employee_no', $employeeNo)
            ->whereDate('effectivity_date', '<=', $payrollDate)
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id')
            ->value('deduction_applied');

        if ($cutoff !== $deductionApplied && $deductionApplied !== 'both') {
            return 0;
        }

        [$year, $month] = array_map('intval', explode('-', $payrollDate));

        $amount = (float) (DB::table('module_tab_employees as mte')
            ->where('mte.module_tab_id', 13)
            ->where('mte.employee_no', $employeeNo)
            ->where('mte.year', $year)
            ->where('mte.month', $month)
            ->value('amount') ?? 0);

        if ($deductionApplied === 'both') {
            return round($amount / 2, 2);
        }

        return round($amount, 2);
    }

    public function payrollDetails($payroll_no)
    {
        $payroll = DB::table('payroll_salary')
            ->where('payroll_no', $payroll_no)
            ->first();

        $employeeTable = (string) $payroll->employment_type_id === EmploymentTypesEnum::REGULAR->value
            ? 'payroll_salary_permanent_employees'
            : 'payroll_salary_employee';

        $employees = DB::table("{$employeeTable} as pse")
            ->leftJoin('employee_information as ei', 'pse.employee_no', '=', 'ei.employee_no')
            ->leftJoin('users', 'users.id', '=', 'ei.user_id')
            ->where('payroll_salary_id', $payroll->id)
            ->select('pse.employee_no', 'pse.monthly_rate', 'pse.position', 'users.name', 'users.id as employee_id')
            ->get();
        $payroll->employees = $employees;

        return $payroll;
    }

    public function employeePayrollRates($payroll)
    {
        $WORK_DAYS = 22;
        $HOURS_DAY = 8;
        $MINS_HOUR = 60;

        [$start_date, $end_date] = $this->resolvePayrollDateRange($payroll);

        $employees = $payroll->employees->map(function ($employee) use (
            $start_date,
            $end_date,
            $WORK_DAYS,
            $HOURS_DAY,
            $MINS_HOUR
        ) {

            $dtr = $this->daily_time_record_service->getDTR([
                'user_id'   => $employee->employee_id,
                'startDate' => $start_date,
                'endDate'   => $end_date,
            ]);

            $summary = $dtr['payroll_value'] ?? [];

            /*
            |--------------------------------------------------------------------------
            | RATES (NO EARLY ROUNDING)
            |--------------------------------------------------------------------------
            */
            $daily_rate  = $employee->monthly_rate / $WORK_DAYS;
            $hourly_rate = $daily_rate / $HOURS_DAY;
            $minute_rate = $hourly_rate / $MINS_HOUR;

            /*
            |--------------------------------------------------------------------------
            | ABSENCES
            |--------------------------------------------------------------------------
            */
            $absent_days   = $summary['absent'] ?? 0;
            $absent_amount = $absent_days * $daily_rate;

            /*
            |--------------------------------------------------------------------------
            | UNDERTIME
            |--------------------------------------------------------------------------
            */
            $total_ut_minutes = $summary['late_undertime'] ?? 0;

            // Split for display only
            $ut_hours   = intdiv($total_ut_minutes, $MINS_HOUR);
            $ut_minutes = $total_ut_minutes % $MINS_HOUR;

            // Compute parts using full precision
            $ut_hours_amount   = $ut_hours * $hourly_rate;
            $ut_minutes_amount = $ut_minutes * $minute_rate;

            // Authoritative undertime amount
            $ut_amount = $total_ut_minutes * $minute_rate;

            /*
            |--------------------------------------------------------------------------
            | TOTAL DEDUCTIONS (USE AUTHORITATIVE VALUES)
            |--------------------------------------------------------------------------
            */
            $total_aut_amount = $absent_amount + $ut_amount;

            return [
                'project_name' => 'sample',

                'employee_no' => $employee->employee_no,
                'name'        => $employee->name,
                'position'    => $employee->position,

                /*
                |--------------------------------------------------------------------------
                | RATES (Rounded Only For Output)
                |--------------------------------------------------------------------------
                */
                'monthly_rate' => number_format($employee->monthly_rate, 2),
                'daily_rate'   => number_format($daily_rate, 6),
                'hourly_rate'  => number_format($hourly_rate, 6),
                'minute_rate'  => number_format($minute_rate, 6),

                /*
                |--------------------------------------------------------------------------
                | ABSENCES
                |--------------------------------------------------------------------------
                */
                'absent_days'   => $absent_days,
                'absent_amount' => number_format($absent_amount, 2),

                /*
                |--------------------------------------------------------------------------
                | UNDERTIME (DISPLAY)
                |--------------------------------------------------------------------------
                */
                'ut_hours'          => $ut_hours,
                'ut_minutes'        => $ut_minutes,
                'ut_hours_amount'   => number_format($ut_hours_amount, 2),
                'ut_minutes_amount' => number_format($ut_minutes_amount, 2),
                'ut_amount'         => number_format($ut_amount, 2),

                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                */
                'total_aut_amount' => number_format($total_aut_amount, 2),
            ];
        });

        return $employees
            ->groupBy('project_name')
            ->map(function ($emps, $project) {
                return [
                    'name' => $project,
                    'employees' => $emps->values()
                ];
            })
            ->values();
    }

    private function resolvePayrollDateRange(object $payroll): array
    {
        $payrollDate = Carbon::parse($payroll->payroll_date);

        if ((string) $payroll->employment_type_id === EmploymentTypesEnum::REGULAR->value) {
            return [
                $payrollDate->copy()->startOfMonth()->format('Y-m-d'),
                $payrollDate->copy()->endOfMonth()->format('Y-m-d'),
            ];
        }

        if (($payroll->cutoff ?? null) === 'first_cutoff') {
            return [
                $payrollDate->copy()->startOfMonth()->format('Y-m-d'),
                $payrollDate->copy()->day(15)->format('Y-m-d'),
            ];
        }

        return [
            $payrollDate->copy()->day(16)->format('Y-m-d'),
            $payrollDate->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    private function splitRegularNetPay(float $netPay): array
    {
        $firstCutoff = round($netPay / 2, 2);
        $secondCutoff = round($netPay - $firstCutoff, 2);

        return [$firstCutoff, $secondCutoff];
    }
}
