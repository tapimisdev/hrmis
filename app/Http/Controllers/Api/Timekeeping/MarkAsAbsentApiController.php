<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkAsAbsentApiController extends Controller
{
    public function mark_as_absent(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        $updatedRows = DB::table('timelogs')
            ->where('employee_no', $validated['employee_no'])
            ->whereDate('date_time', $validated['date'])
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'updated' => $updatedRows,
        ]);
    }

}
