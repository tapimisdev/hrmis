<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelLTOController extends Controller
{

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {

            $data = DB::table('lto_applications as oa')
                ->leftJoin('lto_dates as od', 'oa.id', '=', 'od.lto_application_id')
                ->where('oa.employee_no', $validated['employee_no'])
                ->where('od.date', $validated['date'])
                ->whereIn('oa.status', ['approved', 'pending'])  
                ->where('od.isActive', true)
                ->select(
                    'od.id as offset_date_id',
                    'oa.id as lto_application_id'
                )
                ->first();

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved special order found for this date.',
                ]);
            }

            //  Count active dates BEFORE cancelling
            $totalDates = DB::table('lto_dates')
                ->where('lto_application_id', $data->lto_application_id)
                ->where('isActive', true)
                ->count();

            //  Cancel this specific offset date
            DB::table('lto_dates')
                ->where('id', $data->offset_date_id)
                ->update([
                    'isActive' => false,
                ]);

            //  If this was the last active date, cancel the entire application
            if ($totalDates == 1) {
                DB::table('lto_applications')
                    ->where('id', $data->lto_application_id)
                    ->update([
                        'status' => 'cancelled',
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => $totalDates == 1
                    ? 'Local travel order cancelled completely.'
                    : 'Local travel order date cancelled successfully.',
                'remaining_active_dates' => $totalDates - 1,
            ]);
        });
    }



}
