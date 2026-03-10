<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryEmloyeeService {

    public function activeOrg()
    {
        return DB::table('employee_organization as eo1')
                ->select('eo1.*')
                ->whereRaw('eo1.created_at = (
                    SELECT MAX(eo2.created_at)
                    FROM employee_organization eo2
                    WHERE eo2.employee_no = eo1.employee_no
                )');
    }

    public function activeSalary()
    {
        return DB::table('employee_salary as es1')
                ->select('es1.*')
                ->whereRaw('es1.created_at = (
                    SELECT MAX(es2.created_at)
                    FROM employee_salary es2
                    WHERE es2.employee_no = es1.employee_no
                )');
    }

    public function activeShift()
    {
        return DB::table('employee_shift_work_schedule as sw1')
                ->select('sw1.*')
                ->whereRaw('sw1.id = 
                        (select max(sw2.id) from employee_shift_work_schedule sw2 
                        where sw2.employee_no = sw1.employee_no)');
    }
}