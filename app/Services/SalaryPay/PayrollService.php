<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use App\Enums\FnEnum;
use App\Jobs\Admin\Payroll\PayrollRegistryReport;
use App\Models\User;
use App\Notifications\PayrollBatchCompleted;
use App\Services\DailyTimeRecordService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Throwable;
use function PHPSTORM_META\map;

class PayrollService {

    protected $daily_time_record_service;
    private $date;
    private $cutoff;

    private $eligibile;
    private $not_eligibile;

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_salary as ps')
                ->leftJoin('employment_types as et', 'ps.employment_type_id', '=', 'et.id')
                ->select('ps.*', 'et.name as employment_name', 'et.code as employment_code');

        if (!empty($payload['year'])) {
            $query->whereYear('ps.payroll_date', $payload['year']);
        }

        if (!empty($payload['month'])) {
            $query->whereMonth('ps.payroll_date', $payload['month']);
        }

        if (!empty($payload['cutoff'])) {
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

        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
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
                'ei.account_status')
            ->get();
        
        
        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $emp) {
            $this->checkEligibility($emp);
        }

        $seperatedEmployee = [
            'eligible' => $this->eligibile ?? [],
            'not_eligible' => $this->not_eligibile ?? [],
        ];

        return $seperatedEmployee;
    }

    private function checkEligibility($employee)
    {
        // Prepare payload
        $payload = array_merge($this->getCutoff(), ['user_id' => $employee->user_id]);
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

        if(!$this->hasWorkAndShift($employee->employee_no)) {
            $remarks[] = [
                'text' => 'This Employee has no work or shift schedule during this payroll date',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        if(!$this->hasInformation($employee->employee_no)) {
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

        if (!$this->hasProject($employee->employee_no) 
            && $employee->employment_type_id == EmploymentTypesEnum::COS->value) {
            
            $eligibleRemarks[] = [
                'text' => 'COS employee has no assigned project during the payroll date. Please update.',
                'url'  => route('hris.employee.information', ['employee_no' => $employee->employee_no]),
            ];
        }

        // Check incomplete logs
        if ($incompleteLogs > 0) {
            $remarks[] = [
                'text' => sprintf(
                    "This Employee %s %d missing log%s",
                    $incompleteLogs === 1 ? 'has' : 'have',
                    $incompleteLogs,
                    $incompleteLogs === 1 ? '' : 's'
                ),
                'url' => route('daily-time-record.index', [
                    'employee_no' => $employee->employee_no,
                    'month' => \Carbon\Carbon::parse($this->date)->format('m'),
                    'year' => \Carbon\Carbon::parse($this->date)->format('Y'),
                ]),
            ];
        }

        // Determine eligibility
        $employee->remarks = $remarks ?: $eligibleRemarks;

        if (empty($remarks)) {
            $this->eligibile[] = $employee;
        } else {
            $this->not_eligibile[] = $employee;
        }
    }

    private function hasWorkAndShift($emp_no)
    {
        $schedule = DB::table('employee_shift_work_schedule as esw')
            ->leftJoin('shifts as s', 'esw.shift_id', '=', 's.id')
            ->select(
                'esw.shift_id',
                'esw.work_schedule_id',
                's.working_hours'
            )
            ->where('esw.employee_no', $emp_no)
            ->where('esw.effectivity_date', '<=', $this->date)
            ->first();

        if($schedule) {
            return true;
        }

        return false;
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
        $employee_salary = DB::table('employee_salary')
            ->where('employee_no', $emp_no)
            ->whereDate('effectivity_date', '<=', $this->date)
            ->orderByDesc('effectivity_date')
            ->first();

        return !is_null($employee_salary);
    }

    private function hasProject($emp_no) {

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
        
        if(!is_null($projects_employee)) {
            return true;
        }

        return false;

    }

    public function generatePayrollRegistryReport($payload, $payroll_id)
    {
        $employees = collect($this->getEligibleEmployees($payload));
        $eligibleEmployees = $employees->get('eligible', []);

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

        foreach ($eligibleEmployees as $employee) {
            $batch->add(new PayrollRegistryReport($employee, $payroll_id));
        }

        return $batch->id;
    }

    public function createPayroll($payload)
    {
        $payroll_no = generateNo('REF-', 4);
        
        // Insert payroll and get its ID
        $payroll_id = DB::table('payroll_salary')->insertGetId([
            'label' => $payload['label'],
            'payroll_no' => $payroll_no,

            'period_covered' => // month year from 1 - 15 or 16 - end of month
                \Carbon\Carbon::parse($payload['date'])->format('F Y') . ' ' .
                ($payload['cutoff'] === 'first_cutoff' ? '1-15' : '16-' . \Carbon\Carbon::parse($payload['date'])->endOfMonth()->format('d')),
            
            'no_employee' => 0,
            'gross_amount' => 0,
            'deduction_amount' => 0,
            'netpay_amount' => 0,
            'processed_by_id' => auth('sanctum')->user()->id,
            'payroll_date' => $payload['date'],
            'cutoff' => $payload['cutoff'],
            'employment_type_id' => $payload['employment_type_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

    private function getCutoff()
    {
        $dateObj = new \DateTime($this->date);
        $year  = $dateObj->format('Y');
        $month = $dateObj->format('m');

        if ($this->cutoff === 'first_cutoff') {
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
            ->map(function($holiday) use ($start_date) {
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

    public function getPayrollRegistry(string $payroll_id, bool $isGrouped = true) 
    {
        $payroll_date = DB::table('payroll_salary')
            ->where('id', $payroll_id)
            ->value('payroll_date');

        $pse = DB::table('payroll_salary_employee as pse')
            ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
            ->where('payroll_salary_id', $payroll_id)
            ->select('pse.*', 'ps.payroll_date')
            ->get();

        // Get all projects for this payroll date
        $projects = DB::table('employee_projects as ep')
            ->join('projects', 'ep.project_id', '=', 'projects.id')
            ->whereDate('start_date', '<=', $payroll_date)
            ->where(function ($query) use ($payroll_date) {
                $query->whereDate('end_date', '>=', $payroll_date)
                    ->orWhereNull('end_date');
            })
            ->select('projects.id', 'projects.name')
            ->get()->unique('id');

        $enriched = $pse->map(function ($d) use ($payroll_date) {

            $deductions = DB::table('payroll_salary_employee_edeductions')
                ->where('payroll_se_id', $d->id)
                ->get();

            $earnings = DB::table('payroll_salary_employee_earnings')
                ->where('payroll_se_id', $d->id)
                ->get();

            $project_id = DB::table('employee_projects')
                ->where('employee_no', $d->employee_no)
                ->whereDate('start_date', '<=', $payroll_date)
                ->where(function ($query) use ($payroll_date) {
                    $query->whereDate('end_date', '>=', $payroll_date)
                        ->orWhereNull('end_date');
                })
                ->orderByDesc('start_date')
                ->value('project_id');

            return (object) [
                'employee_no' => $d->employee_no,
                'name' => strtoupper($d->name),
                'position' => ucfirst($d->position),
                'monthly_rate' => $d->monthly_rate,
                'salary_grade' => $d->salary_grade,
                'ut' => $d->ut,
                'absences' =>  $d->absences,
                'aut' => $d->ut + $d->absences,
                'overtime' => $d->overtime,
                'holiday' => $d->holiday,
                'gsis' => $d->gsis,
                'philhealth' => $d->philhealth,
                'pagibig' => $d->pagibig,
                'w_tax' => $d->w_tax,
                'total_deductions' => $d->total_deductions,
                'total_earnings' => $d->total_earnings,
                'basic_pay' => $d->basic_pay,
                'gross_pay' => $d->gross_pay,
                'net_pay' => $d->net_pay,
                'salary_adjustment' => $d->salary_adjustment,
                'deductions' => $deductions ?? [],
                'earnings' => $earnings ?? [],
                'project_id' => $project_id
            ];
        });

        if ($isGrouped) {
            // Group employees by project
            $projectGroups = [];

            foreach ($enriched as $employee) {
                $emp_project = $projects->firstWhere('id', $employee->project_id);

                $projectId = $emp_project->id ?? 'others';
                $projectName = $emp_project->name ?? 'No Projects';

                if (!isset($projectGroups[$projectId])) {
                    $projectGroups[$projectId] = [
                        'name' => $projectName,
                        'employees' => []
                    ];
                }

                $projectGroups[$projectId]['employees'][] = [
                    'employee_no' => $employee->employee_no,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'salary_earned' => $employee->basic_pay,
                    'ut' => $employee->ut,
                    'absences' =>  $employee->absences,
                    'aut' => $employee->ut + $employee->absences,
                    'overtime' => $employee->overtime,
                    'holiday' => $employee->holiday,
                    'total_salary' => $employee->gross_pay,
                    'deductions' => $employee->deductions,
                    'earnings' => $employee->earnings,
                    'adjustment' => $employee->salary_adjustment,
                    'net_salary' => $employee->net_pay
                ];
            }

            return response()->json(array_values($projectGroups));

        } else {
            // Return flat list without grouping
            $flatList = $enriched->map(function ($employee) {
                return [
                    'employee_no' => $employee->employee_no,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'salary_earned' => $employee->basic_pay,
                    'ut' => $employee->ut,
                    'absences' =>  $employee->absences,
                    'aut' => $employee->ut + $employee->absences,
                    'overtime' => $employee->overtime,
                    'holiday' => $employee->holiday,
                    'total_salary' => $employee->gross_pay,
                    'deductions' => $employee->deductions,
                    'earnings' => $employee->earnings,
                    'adjustment' => $employee->salary_adjustment,
                    'net_salary' => $employee->net_pay,
                    'project_id' => $employee->project_id
                ];
            });

            return response()->json($flatList);
        }
    }

    public function payrollDetails($payroll_no)
    {
        $payroll = DB::table('payroll_salary')
                    ->where('payroll_no', $payroll_no)
                    ->first();

        return $payroll;
    }

    public function employeePayrollRates($payroll_id)
    {
        $employees = DB::table('payroll_salary_employee as pse')
            ->where('pse.payroll_salary_id', $payroll_id)
            ->leftJoinSub(
                DB::table('employee_projects as ep')
                    ->select('ep.*')
                    ->whereRaw('ep.id IN (SELECT MAX(id) FROM employee_projects GROUP BY employee_no)'),
                'latest_proj',
                'pse.employee_no',
                '=',
                'latest_proj.employee_no'
            )
            ->leftJoin('projects as p', 'latest_proj.project_id', '=', 'p.id')
            ->select(
                'pse.*',
                'latest_proj.*',
                'p.name as project_name'
            )
            ->get()
            ->map(function ($employee) {
                $employee->aut = $employee->ut + $employee->absences;

                

                return $employee;
            });

        $grouped = $employees->groupBy('project_name');

        $data = [];

        foreach ($grouped as $unitName => $unitEmployees) {
            $data[$unitName] = $unitEmployees->map(function ($employee) {
                return [
                    'name'         => $employee->name,
                    'position'     => $employee->position,
                    'monthly_rate' => $employee->monthly_rate,
                    'daily_rate'   => number_format($employee->monthly_rate / 22, 2),
                    'hourly_rate'  => number_format(($employee->monthly_rate / 22) / 8, 2),
                    'minute_rate'  => number_format((($employee->monthly_rate / 22) / 8) / 60, 2),
                ];
            })->toArray(); 
        }

        return $data;

    }

}
    