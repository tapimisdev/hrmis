<?php

namespace App\Services\SalaryPay;

use App\Enums\EmploymentTypesEnum;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetEmployeeService {
    
    protected $payroll_id;
    protected $isGrouped;

    public $employees;

    public function __construct($payroll_id, $isGrouped)
    {
        $this->payroll_id = $payroll_id;
        $this->isGrouped = $isGrouped;
    }
    
    public function getAndMapEmployeeSalary()
    {
        $payroll = DB::table('payroll_salary')
            ->where('id', $this->payroll_id)
            ->select('payroll_date', 'employment_type_id')
            ->first();

        $payroll_date = $payroll->payroll_date;
        $employment_type_id = $payroll->employment_type_id;

        // COS
        if($employment_type_id == EmploymentTypesEnum::COS->value) {
            $pse = DB::table('payroll_salary_employee as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->where('payroll_salary_id', $this->payroll_id)
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
                    'id' => $d->id,
                    'employee_no' => $d->employee_no,
                    'name' => strtoupper($d->name),
                    'position' => ucfirst($d->position),
                    'monthly_rate' => $d->monthly_rate,
                    'salary_grade' => $d->salary_grade,
                    'ut' => $d->ut,
                    'absences' => $d->absences,
                    'overtime' => $d->overtime,
                    'holiday' => $d->holiday,
                    'gsis' => $d->gsis,
                    'philhealth' => $d->philhealth,
                    'pagibig' => $d->pagibig,
                    'w_tax' => $d->w_tax,
                    'total_earnings' => $d->total_earnings,
                    'ewt_2' => $d->ewt_2,
                    'percentage_tax_3' => $d->percentage_tax_3,
                    'tax_ewt_5' => $d->tax_ewt_5,
                    'basic_pay' => $d->basic_pay,
                    'gross_pay' => $d->gross_pay,
                    'net_pay' => $d->net_pay,
                    'salary_adjustment' => $d->salary_adjustment,
                    'deductions' => $deductions ?? [],
                    'earnings' => $earnings ?? [],
                    'project_id' => $project_id,
                    'remarks' => $d->remarks ?? null,
                ];
            });

            if ($this->isGrouped) {
                // Group employees by project
                $this->employees = [];

                foreach ($enriched as $employee) {
                    $emp_project = $projects->firstWhere('id', $employee->project_id);

                    $projectId = $emp_project->id ?? 'others';
                    $projectName = $emp_project->name ?? 'No Projects';

                    if (!isset($this->employees[$projectId])) {
                        $this->employees[$projectId] = [
                            'name' => $projectName,
                            'employees' => []
                        ];
                    }

                    $this->employees[$projectId]['employees'][] = [
                        'id' => $employee->id,
                        'employee_no' => $employee->employee_no,
                        'name' => $employee->name,
                        'position' => $employee->position,
                        'monthly_rate' => $employee->monthly_rate,
                        'salary_earned' => $employee->basic_pay,
                        'aut' => $employee->ut + $employee->absences,
                        'ut' => $employee->ut,
                        'absences' => $employee->absences,
                        'overtime' => $employee->overtime,
                        'holiday' => $employee->holiday,
                        'total_salary' => $employee->gross_pay,
                        'ewt_2' => $employee->ewt_2,
                        'percentage_tax_3' => $employee->percentage_tax_3,
                        'tax_ewt_5' => $employee->tax_ewt_5,
                        'w_tax' => $employee->w_tax,
                        'deductions' => $employee->deductions,
                        'earnings' => $employee->earnings,
                        'adjustment' => $employee->salary_adjustment,
                        'net_salary' => $employee->net_pay,
                        'remarks' => $employee->remarks ?? '',
                    ];
                }

                $this->employees = array_values($this->employees);

            } else {
                // Return flat list without grouping
                $this->employees = $enriched->map(function ($employee) {
                    return [
                        'employee_no' => $employee->employee_no,
                        'name' => $employee->name,
                        'position' => $employee->position,
                        'monthly_rate' => $employee->monthly_rate,
                        'salary_earned' => $employee->basic_pay,
                        'aut' => $employee->ut + $employee->absences,
                        'ut' => $employee->ut,
                        'absences' => $employee->absences,
                        'overtime' => $employee->overtime,
                        'holiday' => $employee->holiday,
                        'total_salary' => $employee->gross_pay,
                        'deductions' => $employee->deductions,
                        'earnings' => $employee->earnings,
                        'adjustment' => $employee->salary_adjustment,
                        'net_salary' => $employee->net_pay,
                        'project_id' => $employee->project_id,
                        'remarks' => $employee->remarks ?? '',
                    ];
                });

            }
        }

        // PERMANENT
        if($employment_type_id == EmploymentTypesEnum::REGULAR->value) {

            $pse = DB::table('payroll_salary_permanent_employees as pse')
                ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                ->where('payroll_salary_id', $this->payroll_id)
                ->select('pse.*', 'ps.payroll_date')
                ->get()
                ->map(function ($employee) {
                    $psped = DB::table('payroll_salary_permanents_employee_deductions')
                                    ->where('pspe_id', $employee->id)
                                    ->get();
                    $employee->aut = (float) $employee->ut + (float) $employee->absences;

                    $employee->deductions = $psped;

                    return $employee;
                });

            $this->employees = $pse;
        }
        
    }

}