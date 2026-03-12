<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryEmloyeeService
{
    public function activeOrg($employee_no = null, $endDate = null)
    {
        return DB::table('employee_organization as eo1')
            ->where(
                'eo1.id',
                DB::table('employee_organization as eo2')
                    ->select('eo2.id')
                    ->whereColumn('eo2.employee_no', 'eo1.employee_no')
                    ->when($endDate, fn($q) => $q->where('eo2.created_at', '<=', $endDate))
                    ->orderByDesc('eo2.created_at')
                    ->orderByDesc('eo2.id')
                    ->limit(1)
            )
            ->when($employee_no, fn($q) => $q->where('eo1.employee_no', $employee_no));
    }

    public function activeSalary($employee_no = null, $endDate = null)
    {
        return DB::table('employee_salary as es1')
            ->where(
                'es1.id',
                DB::table('employee_salary as es2')
                    ->select('es2.id')
                    ->whereColumn('es2.employee_no', 'es1.employee_no')
                    ->when($endDate, fn($q) => $q->where('es2.created_at', '<=', $endDate))
                    ->orderByDesc('es2.created_at')
                    ->orderByDesc('es2.id')
                    ->limit(1)
            )
            ->when($employee_no, fn($q) => $q->where('es1.employee_no', $employee_no));
    }

    public function activeShift($employee_no = null, $endDate = null)
    {
        return DB::table('employee_shift_work_schedule as sw1')
            ->where(
                'sw1.id',
                DB::table('employee_shift_work_schedule as sw2')
                    ->select('sw2.id')
                    ->whereColumn('sw2.employee_no', 'sw1.employee_no')
                    ->when($endDate, fn($q) => $q->where('sw2.created_at', '<=', $endDate))
                    ->orderByDesc('sw2.created_at')
                    ->orderByDesc('sw2.id')
                    ->limit(1)
            )
            ->when($employee_no, fn($q) => $q->where('sw1.employee_no', $employee_no));
    }
}
