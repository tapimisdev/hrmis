<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelOffsetApiController extends Controller
{

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {

            $data = DB::table('offset_applications as oa')
                ->leftJoin('offset_dates as od', 'oa.id', '=', 'od.offset_application_id')
                ->where('oa.employee_no', $validated['employee_no'])
                ->where('od.date', $validated['date'])
                ->where('oa.status', 'approved')
                ->where('od.isActive', true)
                ->select(
                    'od.id as offset_date_id',
                    'oa.id as offset_application_id'
                )
                ->first();

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved offset found for this date.',
                ]);
            }

            // 🔹 Count active dates BEFORE cancelling
            $totalDates = DB::table('offset_dates')
                ->where('offset_application_id', $data->offset_application_id)
                ->where('isActive', true)
                ->count();

            // 🔹 Cancel this specific offset date
            DB::table('offset_dates')
                ->where('id', $data->offset_date_id)
                ->update([
                    'isActive' => false,
                ]);

            // 🔹 If this was the last active date, cancel the entire application
            if ($totalDates == 1) {
                DB::table('offset_applications')
                    ->where('id', $data->offset_application_id)
                    ->update([
                        'status' => 'cancelled',
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => $totalDates == 1
                    ? 'Offset cancelled completely.'
                    : 'Offset date cancelled successfully.',
                'remaining_active_dates' => $totalDates - 1,
            ]);
        });
    }



}
