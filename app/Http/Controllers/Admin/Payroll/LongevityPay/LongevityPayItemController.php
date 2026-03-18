<?php

namespace App\Http\Controllers\Admin\Payroll\LongevityPay;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LongevityPayItemController extends Controller
{
    public function update($payroll_no, $payroll_emp_id, Request $request)
    {
        $validatedData = $request->validate([
            'adjustment' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $payroll = DB::table('payroll_longevity_pay')
                ->where('payroll_no', $payroll_no)->first();

            if (!$payroll) {
                abort(404, 'Payroll not found.');
            }

            $adjustment = (float) $validatedData['adjustment'];
            $table = 'payroll_longevity_pay_employee';
            $item = DB::table($table)->where('id', $payroll_emp_id)->first();

            if (!$item) {
                abort(404, 'Longevity pay record not found.');
            }

            $remarks = $validatedData['remarks'] ?? $item->remarks;
            $baseTotal = (float) $item->total;
            $newNetPay = $baseTotal + $adjustment;

            DB::table($table)
                ->where('id', $payroll_emp_id)
                ->update([
                    'adjustments' => $adjustment,
                    'remarks' => $remarks,
                    'net_pay' => $newNetPay,
                ]);

            $updated_item = DB::table($table)
                ->where('id', $payroll_emp_id)
                ->first();

            DB::commit();

            return response([
                'fetch_data' => true,
                'data' => $updated_item,
                'message' => 'Longevity pay item updated successfully',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'update longevity pay item failed',
            ], 500);
        }
    }
}
