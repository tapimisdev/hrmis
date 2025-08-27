<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
     
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
    }

    public function index(Request $request, string $employee_no)
    {

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $data = $employee_no && $isExists ? $this->employeeService->getEmployee('personal', $employee_no) : [];

        return view('admin.pages.hris.personal', compact('isExists', 'employee_no', 'data'));

    }

    public function save(Request $request, string $employee_no)
    {

        $birth_certificate = '';
        $marriage_certificate = '';

        $request->validate($this->rules(), $this->messages());          

        DB::beginTransaction();

        try {

            DB::table('employee_personal')->updateOrInsert(
                ['employee_no' => $employee_no],
                [
                    'profile' => $request->profile ?? null,
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
                    'birth_certificate' => $birth_certificate ?? null,
                    'marriage_certificate' => $marriage_certificate ?? null,
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
                ]
            );


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Personal information of  #' . $employee_no . ' was savedsuccessfully.',
                'redirect' => ''
            ]);
            
        } catch(\Exception $e) {
             DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
        

    }

    protected function rules(?string $employee_no = null) {
        return [
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

    protected function messages() {
        return [
           
            'firstname.required' => 'The first name is required.',
            'lastname.required' => 'The last name is required.',
            'suffix.in' => 'The suffix must be one of the following: jr, sr, I, II, III, IV, or V.',
            'civil_status.in' => 'The civil status must be one of the following: single, married, divorced, separated, widowed, or annulled.',
            'sex.in' => 'The sex must be either male or female.',
            'citizenship_type.required_with' => 'The citizenship type is required when citizenship is provided.',
            'country.required_if' => 'The country is required when citizenship is dual citizenship.',
            'mobile_number.regex' => 'The mobile number format is invalid. It should start with 09 and be followed by 9 digits.',
            'email.email' => 'The email must be a valid email address.',
            'email.required' => 'The email is required.',
            'email.unique' => 'The email is already taken.',
        ];
    }
}
