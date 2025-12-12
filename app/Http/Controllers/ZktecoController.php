<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZktecoController extends Controller
{
    public function cdata(Request $request)
    {
        $rawInput = file_get_contents('php://input');
        $parts = explode("\t", trim($rawInput));

        $biodId    = $parts[0] ?? null;
        $timestamp = $parts[1] ?? null;
        $fn        = $parts[2] ?? null;
        $type      = $parts[3] ?? null;
        
        DB::beginTransaction();

        try {
            Log::info('ADMS Data Received', [
                'raw_input' => $rawInput,
                'all'       => $request->all(),
                'query'     => array_merge($request->query(), [
                    'Type'      => $type,
                    'Fn'        => $fn,
                    'bio_id'    => $biodId,
                    'Timestamp' => $timestamp,
                ]),
            ]);

            $sn = $request->query('SN');

            $hris = DB::table('employee_information')
                    ->select('user_id', 'employee_no')
                    ->where('biometrics_id', $biodId)
                    ->first();

            $user = User::find($hris->user_id);
            $user_schedule = $user->getShiftAndWorkSchedule();

            // Insert time log
            DB::table('timelogs')->insert([
                'user_id'          => $hris->user_id,
                'employee_no'      => $hris->employee_no,
                'date_time'        => $timestamp,
                'fn'               => $fn,
                'biometric_sn'     => $sn,
                'shift_id'         => $user_schedule['shift_id'] ?? null,
                'work_schedule_id' => $user_schedule['work_schedule_id'] ?? null,
                'created_at'       => now('Asia/Manila'),
                'updated_at'       => now('Asia/Manila'),
            ]);

            DB::commit();

            return response("OK", 200);
        } catch (\Exception $e) {
            DB::rollBack();

            // Insert error info into biometric_errors table
            DB::table('biometric_errors')->insert([
                'biometric_id'  => $biodId,
                'date_time'     => $timestamp,
                'fn'            => $fn,
                'type'          => $type,
                'biometric_sn'  => $request->query('SN'),
                'raw_input'     => $rawInput,
                'error_message' => $e->getMessage(),
                'stack_trace'   => $e->getTraceAsString(),
                'created_at'    => now('Asia/Manila'),
                'updated_at'    => now('Asia/Manila'),
            ]);

            return response('fail', 500);
        }
    }
}
