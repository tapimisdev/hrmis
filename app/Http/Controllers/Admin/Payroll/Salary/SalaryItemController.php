<?php

namespace App\Http\Controllers\Admin\Payroll\Salary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryItemController extends Controller
{
    public function update($id, Request $request)
    {
        $validatedData = $request->validate([
            'adjustment' => 'required|Numeric'
        ]);

        DB::beginTransaction();
        try {
            $salary_item = DB::table('payroll_salary_employee')->where('id', $id)->first();

            if (!$salary_item) {
                abort(404, 'Salary record not found.');
            }

            $adjustment = (float) $validatedData['adjustment'];

            // Compute new net pay
            $new_net_pay = $salary_item->gross_pay + $adjustment;

            // Update record in database
            DB::table('payroll_salary_employee')
                ->where('id', $id)
                ->update([
                    'salary_adjustment' => $adjustment,
                    'net_pay' => $new_net_pay,
                    'updated_at' => now(),
                ]);

            // Get updated record
            $updated_salary_item = DB::table('payroll_salary_employee')
                                        ->where('id', $id)->first();

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
