<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\ProfileRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        if(request()->ajax()){
            $user = request()->user();

            $employee_personal = DB::table('employee_personal as ep')
                ->leftJoin('employee_information as ei', 'ep.employee_no', '=', 'ei.employee_no')
                ->where('ei.user_id', $user->id)
                ->select([
                    'ep.employee_no',
                    'ep.*',
                    'ei.date_hired_organization',
                    'ei.date_hired_company',
                    'ei.biometrics_id',
                    'ei.created_at',
                    'ei.updated_at',
                ])
                ->first();

            if ($employee_personal->profile) {
                $employee_personal->profile = Storage::url('public/users/' . $employee_personal->employee_no . '/profile-image/' . $employee_personal->profile);
            } else {
                $employee_personal->profile = 'https://ui-avatars.com/api/?name='
                    . urlencode(($employee_personal->firstname ?? '?') . ' ' . ($employee_personal->lastname ?? '?'))
                    . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
            }

            return response()->json([
                'user' => $user,
                'personal' => $employee_personal
            ]);

        }

        $session_id = session()->getId();

        return view('employee.pages.profile.index', compact('session_id'));
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $employee_no = $data['employee_no'] ?? $user->employee_no;

            // ==============================
            // HANDLE PROFILE UPLOAD
            // ==============================
            if ($request->hasFile('profile') && $request->file('profile')->isValid()) {

                $file = $request->file('profile');

                $path = 'public/users/' . $employee_no . '/profile-image';

                // Ensure directory exists
                Storage::makeDirectory($path);

                // Get old profile
                $oldProfile = DB::table('employee_personal')
                    ->where('employee_no', $employee_no)
                    ->value('profile');

                // Delete old profile if exists
                if ($oldProfile && Storage::exists($path . '/' . $oldProfile)) {
                    Storage::delete($path . '/' . $oldProfile);
                }

                // Save new profile (reference-based naming)
                $filename = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs($path, $filename);

                $data['profile'] = $filename;
            } else {
                unset($data['profile']); // keep old profile
            }

            // ==============================
            // UPDATE EMPLOYEE PERSONAL
            // ==============================
            DB::table('employee_personal')
                ->where('employee_no', $employee_no)
                ->update([
                    'firstname' => $data['firstname'] ?? null,
                    'middlename' => $data['middlename'] ?? null,
                    'lastname' => $data['lastname'] ?? null,
                    'suffix' => $data['suffix'] ?? null,
                    'birthday' => $data['birthday'] ?? null,
                    'age' => $data['age'] ?? null,
                    'civil_status' => $data['civil_status'] ?? null,
                    'sex' => $data['sex'] ?? null,
                    'blood_type' => $data['blood_type'] ?? null,

                    'present_block' => $data['present_block'] ?? null,
                    'present_street' => $data['present_street'] ?? null,
                    'present_subdivision' => $data['present_subdivision'] ?? null,
                    'present_barangay' => $data['present_barangay'] ?? null,
                    'present_city' => $data['present_city'] ?? null,
                    'present_province' => $data['present_province'] ?? null,
                    'present_zip' => $data['present_zip'] ?? null,

                    'permanent_block' => $data['permanent_block'] ?? null,
                    'permanent_street' => $data['permanent_street'] ?? null,
                    'permanent_subdivision' => $data['permanent_subdivision'] ?? null,
                    'permanent_barangay' => $data['permanent_barangay'] ?? null,
                    'permanent_city' => $data['permanent_city'] ?? null,
                    'permanent_province' => $data['permanent_province'] ?? null,
                    'permanent_zip' => $data['permanent_zip'] ?? null,

                    'gsis_no' => $data['gsis_no'] ?? null,
                    'pagibig_no' => $data['pagibig_no'] ?? null,
                    'philhealth_no' => $data['philhealth_no'] ?? null,
                    'sss_no' => $data['sss_no'] ?? null,
                    'tin_no' => $data['tin_no'] ?? null,
                    'philsys_no' => $data['philsys_no'] ?? null,

                    'profile' => $data['profile'] ?? DB::raw('profile'),
                    'updated_at' => now(),
                ]);

            // ==============================
            // UPDATE EMPLOYEE INFORMATION
            // ==============================
            DB::table('employee_information')
                ->where('employee_no', $employee_no)
                ->update([
                    'biometrics_id' => $data['biometrics_id'] ?? null,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}
