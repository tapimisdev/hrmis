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

class CivilServiceController extends Controller
{
    
    public $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;    
    }

    public function index(Request $request, ? string $employee_no = null)
    {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;

        return view('admin.pages.hris.civil-service', compact('isEdit', 'id', 'isExists', 'employee_no'));

    }

     public function handleFile(string $employee_no, object $file) {
        
        $filename = $this->generateService::filename('randomized');
        $extension = $file->extension();

        $filename = $filename . '.' . $extension;

        $file->storeAs(
            'uploads/employees/' . $employee_no . '/civil-service' , 
            $filename, 
            'public'
        );   
        
        return $filename;

    }

    public function save(string $employee_no, Request $request) {

        $payload = $request->all();

        $validator = Validator::make($payload, [
            'id' => [
                'nullable',
            ],
            'certification' => 'required|string|max:255',
            'rating' => 'required|string|max:255',
            'date_exam' => 'required|string|max:255',
            'place_exam' => 'required|string|max:255',
            'license_no' => 'required|string|max:255',
            'date_validity' => 'required|date',
            'documents' => 'nullable|file|mimes:docs,docx,pdf,jpg,png,jpeg',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'field error', 
                'errors'  => $validator->errors()           
            ], 422);
        }
        
        DB::beginTransaction();

        try {

            $exists = DB::table('employee_civil_service')
                ->where('id', $payload['id'])
                ->where('employee_no', $employee_no)
                ->exists();

            $data = [
                'certification'  => $payload['certification'],
                'rating' => $payload['rating'],
                'date_exam'   => $payload['date_exam'],
                'place_exam'  => $payload['place_exam'],
                'license_no'  => $payload['license_no'],
                'date_validity'  => $payload['date_validity'],
                'updated_at' => now(),
                'created_at' => now(),
            ];

            if (!empty($payload['documents']) && $payload['documents'] instanceof UploadedFile) {
                $data['documents'] = $this->handleFile($employee_no, $payload['documents']);
            }
                      
            DB::table('employee_civil_service')->updateOrInsert(
                [
                    'id'          => $payload['id'],
                    'employee_no' => $employee_no
                ],
                $data
            );

            DB::commit();

            $redirect = $exists ? '' : '_self';

            return response()->json([
                'status' => 'success',
                'message' => 'changes has been saved',
                'redirect' => $redirect
            ]);

        } catch(\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(string $employee_no, Request $request)
    {

        DB::beginTransaction();

        try {

            DB::table('employee_civil_service')
                ->where('employee_no', $employee_no)
                ->where('id', $request->id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'civil service record has been deleted.',
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
