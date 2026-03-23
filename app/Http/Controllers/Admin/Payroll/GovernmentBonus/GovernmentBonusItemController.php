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
            'adjustment' => 'nullable|numeric',
            'bonus_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $payroll = DB::table('payroll_government_bonus as pgb')
                ->leftJoin('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
                ->where('payroll_no', $payrollNo)
                ->select('pgb.*', 'gbt.computation_type')
                ->first();

            if (!$payroll) {
                abort(404, 'Payroll not found.');
            }

            $table = 'payroll_government_bonus_employee';
            $item = DB::table($table)->where('id', $payrollEmpId)->first();

            if (!$item) {
                abort(404, 'Government bonus payroll record not found.');
            }

            $remarks = $validatedData['remarks'] ?? $item->remarks;
            $bonusAmount = (float) ($validatedData['bonus_amount'] ?? $item->bonus_amount);
            $adjustment = (float) ($validatedData['adjustment'] ?? $item->adjustments);

            if ($payroll->computation_type === 'manual') {
                $total = $bonusAmount;
                $adjustment = 0;
                $newNetPay = $total;
            } else {
                $total = (float) $item->total;
                $newNetPay = $total + $adjustment;
            }

            DB::table($table)
                ->where('id', $payrollEmpId)
                ->update([
                    'bonus_amount' => $bonusAmount,
                    'total' => $total,
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
