<?php

namespace App\Http\Controllers\Admin\Payroll\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\Steps\ValidateCreatePayrollRequest;
use App\Services\SalaryPayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryApiController extends Controller
{
    protected $salary_payroll_service;

    public function __construct(SalaryPayrollService $salary_payroll_service)
    {
        $this->salary_payroll_service = $salary_payroll_service;
    }
    public function getList(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'month' => 'required|integer|min:1|max:12',
            'cutoff' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:draft,pending,approved,for_releasing,completed,cancelled',
        ]);

        $list = $this->salary_payroll_service->getPayrolls($validated);

        return response(['data' => $list, 'status' => 'success'], 200);
    }

    public function validateAndGetEmployee(ValidateCreatePayrollRequest $request)
    {
        $validatedData = $request->validated();

        $employees = $this->salary_payroll_service->getEligibleEmployees($validatedData);

        return response(['data' => $employees, 'success'], 200);
    }

    public function getAdjustments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $holidays = $this->salary_payroll_service->getHolidays($validated);
        $suspensions = $this->salary_payroll_service->getSuspensions($validated);

        $events = $holidays->merge($suspensions);

        return response()->json($events);
    }

   public function getPayrollRegistry($payroll_id) 
   {
        $payroll_date = DB::table('payroll_salary')
            ->where('id', '=', $payroll_id)
            ->value('payroll_date');

        $pse = DB::table('payroll_salary_employee as pse')
                    ->leftJoin('payroll_salary as ps', 'pse.payroll_salary_id', '=', 'ps.id')
                    ->where('payroll_salary_id', $payroll_id)
                    ->select('pse.*', 'ps.payroll_date')
                    ->get();
        
        // Get all projects for this employee
        $projects = DB::table('employee_projects as ep')
                    ->join('projects', 'ep.project_id', '=', 'projects.id')
                    ->whereDate('start_date', '<=', $payroll_date)
                    ->where(function ($query) use ($payroll_date) {
                        $query->whereDate('end_date', '>=', $payroll_date)
                            ->orWhereNull('end_date');
                    })
                    ->select('projects.id', 'projects.name')
                    ->get()->unique('id');
        
        // dd($projects);

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
                'name' => $d->name,
                'position' => $d->position,
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
                'total_deductions' => $d->total_deductions,
                'total_earnings' => $d->total_earnings,
                'basic_pay' => $d->basic_pay,
                'gross_pay' => $d->gross_pay,
                'net_pay' => $d->net_pay,
                'salary_adjustment' => $d->salary_adjustment,
                'deductions' => $deductions,
                'earnings' => $earnings,
                'project_id' => $project_id
            ];
        });
        

        // Group employees by project
        $projectGroups = [];
        
        foreach ($enriched as $employee) {

            $emp_project = $projects->firstWhere('id', $employee->project_id);
            
            $projectId = $emp_project->id ?? 'others';
            $projectName = $emp_project->name ?? 'No Projects';
            
            // dd($projectId, $projectName);

            if(!isset($projectGroups[$projectId])) {
                $projectGroups[$projectId] = [
                    'name' => $projectName,
                    'employees' => []
                ];
            }

            // Calculate values based on your desired output structure
            $projectGroups[$projectId]['employees'][] = [
                'employee_no' => $employee->employee_no,
                'name' => $employee->name,
                'position' => $employee->position,
                'monthly_rate' => $employee->monthly_rate,
                'salary_earned' => $employee->basic_pay, // Or calculate as needed
                'uat' => $employee->ut + $employee->absences,
                'overtime' => $employee->overtime,
                'holiday' => $employee->holiday,
                'total_salary' => $employee->gross_pay,
                'deductions'    => $employee->deductions,
                'earnings'    => $employee->earnings,
                'adjustment'    => $employee->salary_adjustment,
                'net_salary' => $employee->net_pay
            ];

            // dd($projectGroups);
        }

        // dd($projectGroups);
        
        // Convert to indexed array
        $projects = array_values($projectGroups);
        
        return response()->json($projects);
        // return [
        //     'projects' => $projects
        // ];
    }

    public function approvers()
    {
        $approver_id = DB::table('application_approver')
                        ->where('type', 'payroll')
                        ->value('id');

        $user_approvers = DB::table('application_approver_users as au')
                            ->leftJoin('employee_information as ei', 'au.user_id', '=', 'ei.user_id')
                            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                            ->where('au.application_approver_id', $approver_id)
                            ->select('ei.employee_no', 'au.level', 'ep.firstname', 'ep.lastname', 'ep.middlename', 'ei.user_id')
                            ->get();

        // dd($user_approvers);
        return response()->json($user_approvers);
    }
}
