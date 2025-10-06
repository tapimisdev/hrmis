<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use App\Services\GenerateService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    
    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
    }

    public function index(Request $request, ? string $employee_no = null)
    {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $data = $this->employeeService->getEmployee('account', $employee_no) ?? [];

        return view('admin.pages.hris.account', compact('isEdit', 'id', 'data', 'employee_no'));
    }

    public function rules() {
        return [
            'password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'string', 'min:8', 'same:password'],
        ];
    }

    public function save(string $employee_no, Request $request) {

        $payload = $request->all();

        $request->validate($this->rules());          

        DB::beginTransaction();

        try {

            $employee = DB::table('employee_information')
                ->where('employee_no', $employee_no)
                ->first();

            if (!$employee || !$employee->user_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Account not found',
                ], 404);
            }

            if (!empty($payload['password'])) {
                $data['password'] = Hash::make($payload['password']);
                DB::table('users')
                    ->where('id', $employee->user_id)
                    ->update($data);

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Password has been updated.',
                    'redirect' => route('hris.employee.account', ['employee_no' => $employee_no])
                ]);
            }

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $employee_no, Request $request)
    {

        DB::beginTransaction();

        try {

            DB::table('employee_trainings')
                ->where('employee_no', $employee_no)
                ->where('id', $request->id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'training record has been deleted.',
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

}
