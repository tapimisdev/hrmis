<?php

namespace App\Http\Controllers\Admin\Payroll\GovernmentBonus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GovernmentBonusItemController extends Controller
{
    public function update($payrollNo, $payrollEmpId, Request $request)
    {
        $validatedData = $request->validate([
            'adjustment' => 'required|numeric',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $payroll = DB::table('payroll_government_bonus')
                ->where('payroll_no', $payrollNo)
                ->first();

            if (!$payroll) {
                abort(404, 'Payroll not found.');
            }

            $adjustment = (float) $validatedData['adjustment'];
            $table = 'payroll_government_bonus_employee';
            $item = DB::table($table)->where('id', $payrollEmpId)->first();

            if (!$item) {
                abort(404, 'Government bonus payroll record not found.');
            }

            $remarks = $validatedData['remarks'] ?? $item->remarks;
            $baseTotal = (float) $item->total;
            $newNetPay = $baseTotal + $adjustment;

            DB::table($table)
                ->where('id', $payrollEmpId)
                ->update([
                    'adjustments' => $adjustment,
                    'remarks' => $remarks,
                    'net_pay' => $newNetPay,
                ]);

            $updatedItem = DB::table($table)
                ->where('id', $payrollEmpId)
                ->first();

            DB::commit();

            return response([
                'fetch_data' => true,
                'data' => $updatedItem,
                'message' => 'Government bonus payroll item updated successfully',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'message' => $e->getMessage(),
                'status' => 'update government bonus payroll item failed',
            ], 500);
        }
    }
}
