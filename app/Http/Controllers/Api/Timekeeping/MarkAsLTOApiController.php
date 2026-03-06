<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkAsLTOApiController extends Controller
{
    public function mark_as_lto(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:employee_information,employee_no',
            'date' => 'required|date',
            'lto_no' => 'required|string',
            'shift' => 'required|in:morning,afternoon,wholeday',
            'is_hazardous' => 'required|in:yes,no',
            'remarks' => 'nullable',
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],
        ]);

        $employee = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('ei.employee_no', $validated['user_id'])
            ->select(
                'ei.*',
                DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as name")
            )
            ->first();
        
        if(is_null($employee) || !$employee) {
            return;
        }

        $applicationID = DB::table('lto_applications')->insertGetId([
            'user_id'       => $employee->user_id,
            'employee_no'   => $employee->employee_no,
            'name'          => $employee->name,
            'lto_no'         => $validated['lto_no'],
            'isHazardous'   => $validated['is_hazardous'] == 'yes' ? true : false,
            'status'        => 'approved',
            'remarks'       => $validated['remarks'],
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    
        DB::table('lto_dates')->insertGetId([
            'lto_application_id' => $applicationID,
            'date' => $validated['date'],
            'shift'=> $validated['shift'],
        ]);

        // foreach ($approvers as $level => $approverList) {
        //     foreach ($approverList as $userId) {
        //         DB::table('leave_approvals')->insertGetId([
        //             'lto_application_id' => $applicationID,
        //             'user_id'              => $userId,
        //             'level'                => $level,
        //             'status'               => 'pending',
        //         ]);
        //     }
        // }


        // Handle multiple attachments (if any)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {

                $path = 'users/' . $employee->employee_no . '/so-attachments/';
                $attachmentPath = $file->store($path, 'public');

                DB::table('lto_attachments')->insert([
                    'lto_application_id' => $applicationID,
                    'file_path'            => $attachmentPath,
                    'file_name'            => $file->getClientOriginalName(),
                    'file_type'            => $file->getMimeType(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'updated' => 'mark as lto',
        ]);
    }

}
