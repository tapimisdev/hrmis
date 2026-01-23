<?php

namespace App\Http\Controllers\Admin\Payroll\Salary;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryItemController extends Controller
{
    public function update($payroll_no, $payroll_emp_id, Request $request)
    {
        $validatedData = $request->validate([
            'adjustment' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $payroll = DB::table('payroll_salary')->where('payroll_no', $payroll_no)->first();

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

                $old_gross = $salary_item->net_pay - $salary_item->salary_adjustment;
                $new_net_pay = $old_gross + $adjustment;

            }
            
            // COS EMPLOYEE
            elseif ($payroll->employment_type_id == EmploymentTypesEnum::COS->value) {

                $table = 'payroll_salary_employee';

                $salary_item = DB::table($table)->where('id', $payroll_emp_id)->first();

                if (!$salary_item) {
                    abort(404, 'Salary record not found.');
                }

                $new_net_pay = $salary_item->gross_pay - $salary_item->total_deductions + $adjustment;

            } else {
                abort(400, 'Unsupported employment type.');
            }

            $remarks = $validatedData['remarks'];

            // Update
            DB::table($table)
                ->where('id', $payroll_emp_id)
                ->update([
                    'salary_adjustment' => $adjustment,
                    'remarks' => $remarks,
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
