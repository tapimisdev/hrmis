<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
    }

    public function index(Request $request, ? string $employee_no = null)
    {

        $isExists = $employee_no ? $this->employeeService->checkIfEmployeeExists($employee_no) : false;

        if ($employee_no && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $data = $employee_no && $isExists ? $this->employeeService->getEmployee('family', $employee_no) : [];

        return view('admin.pages.hris.family', compact('isExists', 'employee_no', 'data'));

    }

    public function save(Request $request, string $employee_no)
    {

        $request->validate($this->rules(), $this->messages());          

        DB::beginTransaction();

        try {

            DB::table('employee_family')->updateOrInsert(
                [
                    'employee_no' => $employee_no
                ],
                [
                    'spouse_surname' => $request->spouse_surname,
                    'spouse_firstname' => $request->spouse_firstname,
                    'spouse_middlename' => $request->spouse_middlename,
                    'spouse_suffix' => $request->spouse_suffix,
                    'spouse_occupation' => $request->spouse_occupation,
                    'spouse_business_name_employer' => $request->spouse_business_name_employer,
                    'spouse_business_address' => $request->spouse_business_address,
                    'spouse_contact_no' => $request->spouse_contact_no,
                    'father_surname' => $request->father_surname,
                    'father_firstname' => $request->father_firstname,
                    'father_middlename' => $request->father_middlename,
                    'father_suffix' => $request->father_suffix,
                    'mother_surname' => $request->mother_surname,
                    'mother_firstname' => $request->mother_firstname,
                    'mother_middlename' => $request->mother_middlename,
                ]
            );



            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Family Information of  #' . $employee_no . ' was savedsuccessfully.',
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
            'spouse_surname' => 'nullable|string|max:255',
            'spouse_firstname' => 'nullable|string|max:255',
            'spouse_middlename' => 'nullable|string|max:255',
            'spouse_suffix' => 'nullable|string|max:10',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_business_name_employer' => 'nullable|string|max:255',
            'spouse_business_address' => 'nullable|string|max:255',
            'spouse_contact_no' => 'nullable|string|max:20',
            
            'father_surname' => 'nullable|string|max:255',
            'father_firstname' => 'nullable|string|max:255',
            'father_middlename' => 'nullable|string|max:255',
            'father_suffix' => 'nullable|string|max:10',

            'mother_surname' => 'nullable|string|max:255',
            'mother_firstname' => 'nullable|string|max:255',
            'mother_middlename' => 'nullable|string|max:255',
        ];
    }

    protected function messages() {
        return [
            'spouse_surname.string' => 'Spouse surname must be a valid string.',
            'spouse_firstname.string' => 'Spouse first name must be a valid string.',
            'spouse_middlename.string' => 'Spouse middle name must be a valid string.',
            'spouse_suffix.string' => 'Spouse suffix must be a valid string.',
            'spouse_suffix.max' => 'Spouse suffix must not exceed 10 characters.',
            'spouse_occupation.string' => 'Spouse occupation must be a valid string.',
            'spouse_business_name_employer.string' => 'Spouse employer/business name must be a valid string.',
            'spouse_business_address.string' => 'Spouse business address must be a valid string.',
            'spouse_contact_no.string' => 'Spouse contact number must be a valid string.',

            'father_surname.string' => 'Father\'s surname must be a valid string.',
            'father_firstname.string' => 'Father\'s first name must be a valid string.',
            'father_middlename.string' => 'Father\'s middle name must be a valid string.',
            'father_suffix.string' => 'Father\'s suffix must be a valid string.',
            'father_suffix.max' => 'Father\'s suffix must not exceed 10 characters.',

            'mother_surname.string' => 'Mother\'s surname must be a valid string.',
            'mother_firstname.string' => 'Mother\'s first name must be a valid string.',
            'mother_middlename.string' => 'Mother\'s middle name must be a valid string.',
        ];
    }

}
