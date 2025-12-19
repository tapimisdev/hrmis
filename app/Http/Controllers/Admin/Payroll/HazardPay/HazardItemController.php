<?php

namespace App\Http\Controllers\Admin\Payroll\HazardPay;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HazardItemController extends Controller
{
    public function update($payroll_no, $payroll_emp_id, Request $request)
    {
                
        $validatedData = $request->validate([
            'adjustment' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $payroll = DB::table('payroll_hazard_pay')
                ->where('payroll_no', $payroll_no)->first();

            if (!$payroll) {
                abort(404, 'Payroll not found.');
            }

            $adjustment = (float) $validatedData['adjustment'];

            $table = 'payroll_hazard_pay_employee';

            $item = DB::table($table)->where('id', $payroll_emp_id)->first();

            if (!$item) {
                abort(404, 'Hazard pay record not found.');
            }

            $remarks = $validatedData['remarks'] ?? $item->remarks;

            $old_gross = $item->net_pay - $item->adjustments;
            $new_net_pay = $old_gross + $adjustment;

            // Update
            DB::table($table)
                ->where('id', $payroll_emp_id)
                ->update([
                    'adjustments' => $adjustment,
                    'remarks' => $remarks,
                    'net_pay' => $new_net_pay,
                ]);

            // Fetch updated record
            $updated_item = DB::table($table)
                ->where('id', $payroll_emp_id)
                ->first();

            DB::commit();

            return response([
                'fetch_data' => true,
                'data' => $updated_item,
                'message' => 'Hazard pay item updated successfully',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'update hazard pay item failed'
            ], 500);
        }
    }
}
