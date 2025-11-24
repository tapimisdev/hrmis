<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\StoreModuleTabEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleTabEmployeeController extends Controller
{
    public function index(string $slug, string $tab, int $year = 2025)
    {
        $regular_id = EmploymentTypesEnum::REGULAR->value;

        $monthNames = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        $module = DB::table('modules as m')
                    ->leftJoin('module_tabs as  mt', 'm.id', '=', 'mt.module_id')
                    ->select('m.id as module_id', 'mt.id as module_tab_id')
                    ->where('m.slug', $slug)
                    ->where('mt.tab_slug', $tab)
                    ->first();

        if (!$module) {
            return collect(); // Return empty collection if not found
        }

        // Fetch all regular employees with organization info
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
            ->where('eo.employment_type_id', $regular_id)
            ->select(
                'ei.employee_no',
                'ep.suffix',
                'ep.middlename',
                'ep.lastname',
                'ep.firstname',
                'd.code as division_code',
                'd.name as division_name'
            )
            ->orderBy('ep.lastname', 'asc')
            ->get();

        
        $module_tab_id = $module->module_tab_id;
        
        $module_tab_employees = DB::table('module_tab_employees')
                    ->where('module_tab_id', $module_tab_id)
                    ->where('year', $year)
                    ->get()
                    ->groupBy('employee_no');

        // Map employee data with monthly tax amounts
        return $employees->map(function ($employee) use ($module_tab_employees, $module_tab_id, $monthNames) {
            $employeeRecords = $module_tab_employees[$employee->employee_no] ?? [];

            // Initialize month values to 0
            foreach ($monthNames as $month => $monthName) {
                $record = collect($employeeRecords)->firstWhere('month', $month);
                $employee->{$monthName} = $record->amount ?? 0;
            }

            $employee->module_tab_id = $module_tab_id;

            return $employee;
        });
    }

    public function store(StoreModuleTabEmployeeRequest $request) {

        $validatedData = $request->validated();

        // Map month name to number
        $monthNumbers = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];

        $monthNumber = $monthNumbers[strtolower($validatedData['month'])] ?? null;
        if (!$monthNumber) {
            return response([
                'message' => 'Invalid month provided',
                'status' => 'error',
            ], 422);
        }

        $module_tab_employee = DB::table('module_tab_employees')->updateOrInsert(
                                    [
                                        'module_tab_id' => $validatedData['module_tab_id'],
                                        'employee_no' => $validatedData['employee_no'],
                                        'year' => $validatedData['year'],
                                        'month' => $monthNumber,
                                    ],
                                    [
                                        'amount' => $validatedData['amount']
                                    ]
                                );
        
        return response()->json($module_tab_employee);
    }
}
