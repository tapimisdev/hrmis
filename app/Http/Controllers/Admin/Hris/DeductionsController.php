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

class DeductionsController extends Controller
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

        if($request->ajax()) {
            $deduction_id = $request->deduction_id;
            $deduction = DB::table('deductions')->where('id', $deduction_id)->first();
            return response()->json([
                'status' => 'success',
                'data' => $deduction
            ]);
        }

        $id = null;
        $data = DB::table('employee_deductions')
            ->where('employee_no', $employee_no)
            ->where('isActive', true)
            ->get();

        $deductions = DB::table('deductions')->get();

        return view('admin.pages.hris.deductions', compact('id', 'deductions', 'data', 'employee_no', 'isExists'));
    }

    public function rules(array $payload)
    {

        if(isset($payload['deduction'])) {
            return [
                'deduction' => ['required', 'array'],
                'deduction.*' => ['required', 'exists:deductions,id', 'distinct'],

                'first_term' => ['required', 'array'],
                'first_term.*' => ['required', 'numeric'],

                'second_term' => ['required', 'array'],
                'second_term.*' => ['required', 'numeric'],

                'type' => ['required', 'array'],
                'type.*' => ['required', 'in:daily,monthly,divided_by_22'],

                'start_date' => ['required', 'array'],
                'start_date.*' => ['required', 'date'],

                'end_date' => ['nullable', 'array'],
                'end_date.*' => ['nullable', 'date', 'after_or_equal:start_date.*'],
            ];
        }

        return [];
        
    }

    public function save(string $employee_no, Request $request)
    {
        $payload = $request->all();

        $request->validate($this->rules($payload));

        DB::beginTransaction();

        try {

            if(isset($payload['deduction'])) {

                $submitteddeductionIds = array_filter($payload['deduction'] ?? []);

                DB::table('employee_deductions')
                    ->where('employee_no', $employee_no)
                    ->whereNotIn('deduction_id', $submitteddeductionIds)
                    ->update([
                        'isActive' => false,
                        'updated_at' => now(),
                    ]);

                foreach ($submitteddeductionIds as $key => $deduction_id) {
                    $firstTerm  = $payload['first_term'][$key];
                    $secondTerm = $payload['second_term'][$key];
                    $type       = $payload['type'][$key];
                    $startDate  = $payload['start_date'][$key];
                    $endDate    = $payload['end_date'][$key] ?? null;

                    DB::table('employee_deductions')
                        ->where('employee_no', $employee_no)
                        ->where('deduction_id', $deduction_id)
                        ->update([
                            'isActive' => false,
                            'updated_at' => now(),
                        ]);

                    // Insert new active record
                    DB::table('employee_deductions')->insert([
                        'employee_no'  => $employee_no,
                        'deduction_id'   => $deduction_id,
                        'first_term'   => $firstTerm,
                        'second_term'  => $secondTerm,
                        'type'         => $type,
                        'start_date'   => $startDate,
                        'end_date'     => $endDate,
                        'isActive'     => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            } else {
                 DB::table('employee_deductions')
                    ->where('employee_no', $employee_no)
                    ->update([
                        'isActive' => false,
                        'updated_at' => now(),
                    ]);
            }

            

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Deductions for #' . $employee_no . ' saved successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

}
