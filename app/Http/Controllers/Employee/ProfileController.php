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
                $employee_personal->profile = Storage::url('uploads/employees/' . $employee_personal->employee_no . '/profile/' . $employee_personal->profile);
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

        return view('employee.pages.profile.index');
    }

    public function update(ProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated(); // validated data from ProfileRequest

        // Start DB transaction
        DB::beginTransaction();

        try {
            // Handle profile upload if exists
            if ($request->hasFile('profile')) {
                $profileFile = $request->file('profile');
                $employee_no = $data['employee_no'] ?? $user->employee_no;

                // Make directory if not exists
                $path = 'uploads/employees/' . $employee_no . '/profile/';
                Storage::makeDirectory($path);

                // Delete old profile if exists
                $oldProfile = DB::table('employee_personal')->where('employee_no', $employee_no)->value('profile');
                if ($oldProfile && Storage::exists($path . $oldProfile)) {
                    Storage::delete($path . $oldProfile);
                }

                // Save new profile
                $filename = Str::random(20) . '.' . $profileFile->getClientOriginalExtension();
                $profileFile->storeAs($path, $filename);

                $data['profile'] = $filename;
            } else {
                // If profile not uploaded, remove from $data to keep old value
                unset($data['profile']);
            }

            // Update employee_personal table
            DB::table('employee_personal')
                ->where('employee_no', $data['employee_no'])
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
                    'profile' => $data['profile'] ?? DB::raw('profile'), // keep old if not changed
                    'updated_at' => now(),
                ]);

            // Update employee_information table if needed
            DB::table('employee_information')
                ->where('employee_no', $data['employee_no'])
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
