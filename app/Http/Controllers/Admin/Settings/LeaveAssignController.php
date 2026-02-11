<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveAssignController extends Controller
{
    public function index() {
        $leaves = DB::table('leaves')->get();
        $assignLeave = DB::table('leaves_settings')->get();
        return view('admin.pages.settings.leaves.assign', compact('leaves', 'assignLeave'));
    }

    public function save(Request $request)
    {
        $credit_deduct = $request->credit_deduct;

        DB::beginTransaction();

        try {
            foreach ($credit_deduct as $leave_id => $assign_id) {

                $assigned_id = is_numeric($assign_id) ? $assign_id : null;

                $exists = DB::table('leaves_settings')
                    ->where('leave_id', $leave_id)
                    ->exists();

                if ($exists) {
                    DB::table('leaves_settings')
                        ->where('leave_id', $leave_id)
                        ->update([
                            'deduct_credit_id' => $assigned_id,
                            'updated_at' => now()
                        ]);
                } else {
                    DB::table('leaves_settings')->insert([
                        'leave_id' => $leave_id,
                        'deduct_credit_id' => $assigned_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Changes Saved!',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occured while saving leave.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
