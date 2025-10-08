<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\map;

class SalaryPayrollService {

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_salary');

        if (!empty($payload['year'])) {
            $query->whereYear('payroll_date', $payload['year']);
        }

        if (!empty($payload['month'])) {
            $query->whereMonth('payroll_date', $payload['month']);
        }

        if (!empty($payload['cutoff'])) {
            $query->where('cutoff', $payload['cutoff']);
        }

        if (!empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        return $query->get();
    }

}
    