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
            'adjustment' => 'required|Numeric'
        ]);

        DB::beginTransaction();

        try {

            $payroll = DB::table('payroll_salary')->where('id', $payroll_id)->first();

            if(!$payroll) {
                abort(404, 'Payroll not found.');
            }

            if($payroll->employment_type_id == (int) EmploymentTypesEnum::REGULAR->value) {
                $salary_item = DB::table('payroll_salary_permanent_employees')->where('id', $payroll_emp_id)->first();
                if (!$salary_item) {
                    abort(404, 'Salary record not found.');
                }

                $adjustment = (float) $validatedData['adjustment'];
                $new_net_pay = $salary_item->net_pay + $adjustment;

                // Update record in database
                DB::table('payroll_salary_permanent_employees')
                    ->where('id', $payroll_emp_id)
                    ->update([
                        'salary_adjustment' => $adjustment,
                        'net_pay' => $new_net_pay,
                        'updated_at' => now(),
                    ]);

                // Get updated record
                $updated_salary_item = DB::table('payroll_salary_employee')
                                    ->where('id', $payroll_emp_id)->first();

            }

            if($payroll->employment_type_id == (int) EmploymentTypesEnum::COS->value) {
                $salary_item = DB::table('payroll_salary_employee')->where('id', $payroll_emp_id)->first();

                if (!$salary_item) {
                    abort(404, 'Salary record not found.');
                }

                $adjustment = (float) $validatedData['adjustment'];
                $new_net_pay = $salary_item->gross_pay + $adjustment;

                // Update record in database
                DB::table('payroll_salary_employee')
                    ->where('id', $payroll_emp_id)
                    ->update([
                        'salary_adjustment' => $adjustment,
                        'net_pay' => $new_net_pay,
                        'updated_at' => now(),
                    ]);

                // Get updated record
                $updated_salary_item = DB::table('payroll_salary_employee')
                                        ->where('id', $payroll_emp_id)->first();

            }

            DB::commit();
            return response([
                'fetch_data' => true,
                'data' => $updated_salary_item, 
                'message' => 'salary item updated successfully', 
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'update salary item fail'
            ], 500);
        }
    }

}
