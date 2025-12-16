<?php

namespace App\Http\Controllers\Admin\Payroll\Salary;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryItemController extends Controller
{
    public function update($payroll_id, $payroll_emp_id, Request $request)
    {
        $validatedData = $request->validate([
            'adjustment' => 'required|numeric'
        ]);

        DB::beginTransaction();

        try {

            $payroll = DB::table('payroll_salary')->where('id', $payroll_id)->first();

            if (!$payroll) {
                abort(404, 'Payroll not found.');
            }

            $adjustment = (float) $validatedData['adjustment'];

            // REGULAR EMPLOYEE
            if ($payroll->employment_type_id == EmploymentTypesEnum::REGULAR->value) {

                $table = 'payroll_salary_permanent_employees';

                $salary_item = DB::table($table)->where('id', $payroll_emp_id)->first();
                if (!$salary_item) {
                    abort(404, 'Salary record not found.');
                }

                $new_net_pay = $salary_item->net_pay + $adjustment;

            }
            // COS EMPLOYEE
            elseif ($payroll->employment_type_id == EmploymentTypesEnum::COS->value) {

                $table = 'payroll_salary_employee';

                $salary_item = DB::table($table)->where('id', $payroll_emp_id)->first();
                if (!$salary_item) {
                    abort(404, 'Salary record not found.');
                }

                $new_net_pay = $salary_item->gross_pay + $adjustment;

            } else {
                abort(400, 'Unsupported employment type.');
            }

            // Update
            DB::table($table)
                ->where('id', $payroll_emp_id)
                ->update([
                    'salary_adjustment' => $adjustment,
                    'net_pay' => $new_net_pay,
                    'updated_at' => now(),
                ]);

            // Fetch updated record
            $updated_salary_item = DB::table($table)
                ->where('id', $payroll_emp_id)
                ->first();

            DB::commit();

            return response([
                'fetch_data' => true,
                'data' => $updated_salary_item,
                'message' => 'Salary item updated successfully',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'update salary item failed'
            ], 500);
        }
    }


}
