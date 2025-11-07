<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PersonalController extends Controller
{
     
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
        
        $this->middleware('permission:hr.hris.view')->only('index');
        $this->middleware('permission:hr.hris.edit')->only('save');
    }

    public function index(Request $request, string $employee_no)
    {

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $data = $employee_no && $isExists ? $this->employeeService->getEmployee('personal', $employee_no) : [];

        $profile = $data->profile ?? null;

        if (!is_null($profile)) {
            $profile = Storage::url('uploads/employees/' . $employee_no . '/profile/' . $profile);
        } else {
            $profile = 'https://ui-avatars.com/api/?name=' 
                . urlencode(($data->firstname ?? '?') . ' ' . ($data->lastname ?? '?')) 
                . '&background=random&color=fff&font-size=0.5';
        }

        return view('admin.pages.hris.personal', compact('isExists', 'employee_no', 'data', 'profile'));

    }

    public function save(Request $request, string $employee_no)
    {
        $request->validate($this->rules());          

        DB::beginTransaction();

        try {
            $data = [
                'firstname' => $request->firstname ?? null,
                'middlename' => $request->middlename ?? null,
                'lastname' => $request->lastname ?? null,
                'suffix' => $request->suffix ?? null,
                'birthday' => $request->birthday ?? null,
                'civil_status' => $request->civil_status ?? null,
                'sex' => $request->sex ?? null,
                'citizenship' => $request->citizenship ?? null,
                'citizenship_type' => $request->citizenship_type ?? null,
                'country' => $request->country ?? null,
                'present_address' => $request->present_address ?? null,
                'present_province' => $request->present_province ?? null,
                'present_city' => $request->present_city ?? null,
                'permanent_address' => $request->permanent_address ?? null,
                'permanent_province' => $request->permanent_province ?? null,
                'permanent_city' => $request->permanent_city ?? null,
                'mobile_number' => $request->mobile_number ?? null,
                'tel_no' => $request->tel_no ?? null,
                'height' => $request->height ?? null,
                'weight' => $request->weight ?? null,
                'blood_type' => $request->blood_type ?? null,
                'gsis_no' => $request->gsis_no ?? null,
                'pagibig_no' => $request->pagibig_no ?? null,
                'philhealth_no' => $request->philhealth_no ?? null,
                'sss_no' => $request->sss_no ?? null,
                'tin_no' => $request->tin_no ?? null,
                'updated_at' => now()
            ];

            // Handle profile upload
            if ($request->hasFile('profile') && $request->file('profile')->isValid()) {
                $file = $request->file('profile');
                $profile = 'profile_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/uploads/employees/' . $employee_no . '/profile', $profile);
                $data['profile'] = $profile;
            }

            // Handle birth certificate upload
            if ($request->hasFile('birth_certificate') && $request->file('birth_certificate')->isValid()) {
                $file = $request->file('birth_certificate');
                $birth_certificate = 'birth_certificate_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/uploads/employees/' . $employee_no . '/birth_certificate', $birth_certificate);
                $data['birth_certificate'] = $birth_certificate;
            }

            // Handle marriage certificate upload
            if ($request->hasFile('marriage_certificate') && $request->file('marriage_certificate')->isValid()) {
                $file = $request->file('marriage_certificate');
                $marriage_certificate = 'marriage_certificate_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/uploads/employees/' . $employee_no . '/marriage_certificate', $marriage_certificate);
                $data['marriage_certificate'] = $marriage_certificate;
            }

            // Insert/Update only with the fields that should change
            DB::table('employee_personal')->updateOrInsert(
                ['employee_no' => $employee_no],
                $data
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Personal information of #' . $employee_no . ' was saved successfully.',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
    }



    protected function rules(?string $employee_no = null) {
        return [
            'profile' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'suffix' => 'nullable|in:jr,sr,I,II,III,IV,V',
            'civil_status' => 'nullable|in:single,married,divorced,seperated,widowed,anulled',
            'sex' => 'nullable|in:male,female',
            'citizenship_type' => 'nullable|required_with:citizenship',
            'country' => 'required_if:citizenship,dual_citizenship',

            'mobile_number' => 'nullable|regex:/^09\d{9}$/',
            'email' => [
                'nullable',
            ],
        ];
    }

}
