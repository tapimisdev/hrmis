<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelLeaveApiController extends Controller
{
    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        $data = DB::table('leave_applications as la')
            ->leftJoin('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
            ->where('la.employee_no', $validated['employee_no'])
            ->where('ld.date', $validated['date'])
            ->where('la.status', 'approved')
            ->where('ld.isActive', true)
            ->select(
                'ld.id as leave_date_id',
                'la.id as leave_application_id'
            )
            ->first();

        if (is_null($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No approved leave found for this date.',
            ]);
        }

        // 🔹 Count active leave dates BEFORE cancelling
        $totalDates = DB::table('leave_dates')
            ->where('leave_application_id', $data->leave_application_id)
            ->where('isActive', true)
            ->count();

        // 🔹 Cancel this leave date
        DB::table('leave_dates')
            ->where('id', $data->leave_date_id)
            ->update([
                'isActive' => false,
            ]);

        // 🔹 If this was the last active date, cancel the entire leave application
        if ($totalDates == 1) {
            DB::table('leave_applications')
                ->where('id', $data->leave_application_id)
                ->update([
                    'status' => 'cancelled',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => $totalDates == 1
                ? 'Leave cancelled completely.'
                : 'Leave date cancelled successfully.',
            'remaining_active_dates' => $totalDates - 1,
        ]);
    }


}
