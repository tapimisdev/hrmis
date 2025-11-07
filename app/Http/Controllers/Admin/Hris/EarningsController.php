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

class EarningsController extends Controller
{
    
    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
        $this->middleware('permission:hr.hris.view')->only('index');
        $this->middleware('permission:hr.hris.edit')->only('save');
    }

    public function index(Request $request, ? string $employee_no = null)
    {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        if($request->ajax()) {
            $earning_id = $request->earning_id;
            $earning = DB::table('earnings')->where('id', $earning_id)->first();
            return response()->json([
                'status' => 'success',
                'data' => $earning
            ]);
        }

        $id = null;
        $data = DB::table('employee_earnings')
            ->where('employee_no', $employee_no)
            ->where('isActive', true)
            ->get();

        $earnings = DB::table('earnings')->get();

        return view('admin.pages.hris.earnings', compact('id', 'earnings', 'data', 'employee_no', 'isExists'));
    }

    public function rules(array $payload)
    {
        
        if(isset($payload['earning'])) {
            return [
                'earning' => ['required', 'array'],
                'earning.*' => ['required', 'exists:earnings,id', 'distinct'],

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

                'is_taxable' => ['nullable', 'array'],
                'is_taxable.*' => ['boolean'],
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

            if(isset($payload['earning'])) {
            
                $submittedEarningIds = array_filter($payload['earning'] ?? []);

                DB::table('employee_earnings')
                    ->where('employee_no', $employee_no)
                    ->whereNotIn('earning_id', $submittedEarningIds)
                    ->update([
                        'isActive' => false,
                        'updated_at' => now(),
                    ]);

                foreach ($submittedEarningIds as $key => $earning_id) {
                    $firstTerm  = $payload['first_term'][$key];
                    $secondTerm = $payload['second_term'][$key];
                    $type       = $payload['type'][$key];
                    $startDate  = $payload['start_date'][$key];
                    $endDate    = $payload['end_date'][$key] ?? null;
                    $isTaxable  = isset($payload['is_taxable'][$key]) ? true : false;

                    DB::table('employee_earnings')
                        ->where('employee_no', $employee_no)
                        ->where('earning_id', $earning_id)
                        ->update([
                            'isActive' => false,
                            'updated_at' => now(),
                        ]);

                    DB::table('employee_earnings')->insert([
                        'employee_no'  => $employee_no,
                        'earning_id'   => $earning_id,
                        'first_term'   => $firstTerm,
                        'second_term'  => $secondTerm,
                        'type'         => $type,
                        'start_date'   => $startDate,
                        'end_date'     => $endDate,
                        'isTaxable'    => $isTaxable,
                        'isActive'     => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            } else {
                
                DB::table('employee_earnings')
                    ->where('employee_no', $employee_no)
                    ->update([
                        'isActive' => false,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Earnings for #' . $employee_no . ' saved successfully.',
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
