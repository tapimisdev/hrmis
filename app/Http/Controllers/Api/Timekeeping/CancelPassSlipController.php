<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CancelPassSlipController extends Controller
{

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {

            $data = DB::table('obs_applications as oa')
                ->leftJoin('obs_dates as od', 'oa.id', '=', 'od.obs_application_id')
                ->where('oa.employee_no', $validated['employee_no'])
                ->where('od.date', $validated['date'])
                ->whereIn('oa.status', ['approved', 'pending'])  
                ->where('od.isActive', true)
                ->select(
                    'od.id as offset_date_id',
                    'oa.id as obs_application_id'
                )
                ->first();

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved special order found for this date.',
                ]);
            }

            //  Count active dates BEFORE cancelling
            $totalDates = DB::table('obs_dates')
                ->where('obs_application_id', $data->obs_application_id)
                ->where('isActive', true)
                ->count();

            //  Cancel this specific offset date
            DB::table('obs_dates')
                ->where('id', $data->offset_date_id)
                ->update([
                    'isActive' => false,
                ]);

            //  If this was the last active date, cancel the entire application
            if ($totalDates == 1) {
                DB::table('obs_applications')
                    ->where('id', $data->obs_application_id)
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_by' => Auth::id()
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => $totalDates == 1
                    ? 'Special Order cancelled completely.'
                    : 'Special Order date cancelled successfully.',
                'remaining_active_dates' => $totalDates - 1,
            ]);
        });
    }



}
