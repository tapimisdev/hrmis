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
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveCreditController extends Controller
{
    
    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function leave_credits(Request $request, ? string $employee_no = null)
    {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $leaves = $this->employeeService->getLeaveTypes($employee_no);

        return view('admin.pages.hris.leave-credits', compact('isEdit', 'id', 'employee_no', 'isExists', 'leaves'));
    }

    public function save_credits(string $employee_no, Request $request)
    {
        
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'leave_id' => 'required|array',
            'leave_id.*.value' => 'required|numeric|min:1',
        ]);

        $validator->after(function ($validator) use (&$payload) {
            foreach ($payload['leave_id'] as $id => $leave) {
                if (in_array($id, [1, 2])) {
                    $asOf1 = $payload['leave_id'][1]['as_of'] ?? null;
                    $asOf2 = $payload['leave_id'][2]['as_of'] ?? null;

                    if (empty($asOf1) && empty($asOf2)) {
                        $validator->errors()->add('leave_id.1.as_of', 'The "Updated as of" date is required for Leave IDs 1 and 2.');
                    }

                    $sharedAsOf = $asOf1 ?: $asOf2;

                    $payload['leave_id'][1]['as_of'] = $sharedAsOf;
                    $payload['leave_id'][2]['as_of'] = $sharedAsOf;
                } else {
                    if (empty($leave['as_of'])) {
                        $validator->errors()->add("leave_id.$id.as_of", 'The "Updated as of" date is required.');
                    }
                }
            }
        });


        if ($validator->fails()) {
            throw new ValidationException($validator);
        }


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

            if (!empty($payload['leave_id'])) {

                if($payload['forLeaveCard']) {

                    $this->generateLeaveCard(
                        $employee_no,
                        $payload
                    );

                    foreach($payload['leave_id'] as $id => $leave) {
                        DB::table('employee_leave_credits')->updateOrInsert(
                            [
                                'employee_no' => $employee_no,
                                'leave_id' => $id,
                            ],
                            [
                                'amount' => $leave['value'],
                                'effectivity_date' => Carbon::parse($leave['as_of'])->startOfDay(),
                                'updated_at' => now(),
                            ]
                        );
                    }

                }
              

            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Leave credits saved successfully.',
                'redirect' => route('hris.employee.leave-credits', ['employee_no' => $employee_no]),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateLeaveCard(string $employee_no, array $payload)
    {
        if (!isset($payload['leave_id'][1], $payload['leave_id'][2])) {
            return [
                'status' => 'error',
                'message' => 'Incomplete leave data provided.',
                'redirect' => '',
            ];
        }

        // Extract leave values and start date
        $vl_earning = (float) ($payload['leave_id'][1]['value'] ?? 0);
        $sl_earning = (float) ($payload['leave_id'][2]['value'] ?? 0);
        $asOf = Carbon::parse($payload['leave_id'][1]['as_of'] ?? now())->startOfMonth();

        // Fetch latest balances (if any)
        $latest = DB::table('employee_leave_card')
            ->where('employee_no', $employee_no)
            ->latest('year')
            ->latest('id')
            ->first();

        $current_vl = $latest->vl_bal ?? 0;
        $current_sl = $latest->sl_bal ?? 0;

        // Define the loop range — from as_of up to last month of current year
        $end = Carbon::now()->endOfYear()->startOfMonth();

        $inserted = false; // track if any record was generated

        for ($date = $asOf->copy(); $date->lte($end); $date->addMonth()) {
            $year = $date->format('Y');
            $period = strtolower($date->format('F'));

            // Skip if record already exists
            $exists = DB::table('employee_leave_card')
                ->where('employee_no', $employee_no)
                ->where('year', $year)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                continue;
            }

            // Update balances
            $current_vl = round($current_vl + $vl_earning, 2);
            $current_sl = round($current_sl + $sl_earning, 2);

            // Insert new record
            DB::table('employee_leave_card')->insert([
                'employee_no' => $employee_no,
                'period' => $period,
                'year' => $year,
                'particulars' => '',
                'vl_earned' => number_format($vl_earning, 2),
                'vl_aut_w_pay' => 0,
                'vl_bal' => number_format($current_vl, 2),
                'vl_aut_wo_pay' => 0,
                'sl_earned' => number_format($sl_earning, 2),
                'sl_aut_w_pay' => 0,
                'sl_bal' => number_format($current_sl, 2),
                'sl_aut_wo_pay' => 0,
                'remarks' => '',
            ]);

            $inserted = true;
        }

        // Return message depending on whether any record was created
        if ($inserted) {
            return [
                'status' => 'success',
                'message' => 'Leave card successfully generated from ' . $asOf->format('F') . ' to ' . $end->format('F Y'),
                'redirect' => '',
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Leave card already generated for all months from ' . $asOf->format('F') . ' to ' . $end->format('F Y'),
            'redirect' => '',
        ];
    }


    public function leave_card(Request $request, ? string $employee_no = null)
    {
        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $data = $this->employeeService->getEmployee('leave-card', $employee_no) ?? [];


        $data = $this->formatLeaveCard($data);

        return view('admin.pages.hris.leave-card', compact('isEdit', 'id', 'data', 'employee_no'));
    }

    private function formatLeaveCard(object $data) {

        $monthsOrder = [
            'january', 'february', 'march', 'april', 'may', 'june',
            'july', 'august', 'september', 'october', 'november', 'december'
        ];
        

        $grouped = $data
            ->groupBy('year')
            ->map(function ($items) use ($monthsOrder) {
                return $items
                    ->sortBy(function ($item) use ($monthsOrder) {
                        return array_search(strtolower($item->period), $monthsOrder);
                    })
                    ->groupBy('period');
            });

        return $grouped;

    }
    

}
