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
            'leave_id.*.value' => 'required|numeric',
        ]);


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

                foreach($payload['leave_id'] as $id => $leave) {

                    $amount = $leave['value'];
                    $as_of = Carbon::parse($leave['as_of']);

                    $payload = [
                        'leave_id' => $id,
                        'employee_no' => $employee_no,
                        'amount' => $amount,
                        'as_of' => $as_of,
                    ];

                    if($amount > 0) {

                        $this->generateLeaveCard($payload);

                        DB::table('employee_leave_credits')->updateOrInsert(
                            [
                                'employee_no' => $employee_no,
                                'leave_id' => $id,
                            ],
                            [
                                'amount' => $amount,
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

    public function generateLeaveCard($payload)
    {
        $leave_id    = $payload['leave_id'];
        $employee_no = $payload['employee_no'];
        $asOf        = Carbon::parse($payload['as_of'])->startOfMonth();
        $defaultEarned = 1.25;

        // Get the latest leave card for this employee and leave type
        $latest = DB::table('employee_leave_card')
            ->where('leave_type', $leave_id)
            ->where('employee_no', $employee_no)
            ->orderBy('year', 'desc')
            ->orderByRaw("FIELD(period, 'january','february','march','april','may','june','july','august','september','october','november','december') DESC")
            ->first();

        if ($latest) {
            $balance = (float) $latest->balance;

            // Start from the next month after the latest record
            if (strtolower($latest->period) === 'december') {
                $startDate = Carbon::create($latest->year + 1, 1, 1)->startOfMonth();
            } else {
                $startDate = Carbon::parse($latest->year . ' ' . ucfirst($latest->period))->addMonth()->startOfMonth();
            }
            $earned = $defaultEarned; // All earned values are 1.25 for existing data
        } else {
            // No existing data: start from asOf
            $balance = 0;
            $startDate = $asOf;
            $earned = $payload['amount']; // First earned is payload amount
        }

        // End date: December of the start year
        $end = Carbon::create($startDate->year, 12, 31)->endOfMonth();

        for ($date = $startDate->copy(); $date->lte($end); $date->addMonth()) {
            $year   = $date->format('Y');
            $period = strtolower($date->format('F'));

            // For first iteration after no data, use $payload['amount'], then switch to 1.25
            if (!$latest && $balance === 0) {
                $balance = $earned; // first month
            } else {
                $balance += $earned; // subsequent months
            }

            DB::table('employee_leave_card')->updateOrInsert(
                [
                    'leave_type' => $leave_id,
                    'employee_no' => $employee_no,
                    'year' => $year,
                    'period' => $period
                ],
                [
                    'earned'    => $earned,
                    'deduction' => 0,
                    'balance'   => $balance
                ]
            );

            // After first month, earned is always 1.25
            $earned = $defaultEarned;
        }
    }

    public function leave_card(Request $request, string $employee_no, string $leave_id)
    {
        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $data = $this->employeeService->getEmployee('leave-card', $employee_no, $leave_id) ?? [];

        $data = $this->formatLeaveCard($data);

        return view('admin.pages.hris.leave-card', compact('isEdit', 'id', 'data', 'employee_no', 'leave_id'));
    }

    public function add_year(string $employee_no, int $leave_id) {

        $payload = [
            'employee_no' => $employee_no,
            'leave_id' => $leave_id,
            'as_of' => '',
        ];        
        $this->generateLeaveCard($payload);

        return response()->json([
            'status'  => 'success',
            'message' => 'Year Added.',
            'redirect' => route('hris.employee.leave-card', ['employee_no' => $employee_no, 'leave_id' => $leave_id]),
        ]);

    }

    public function remove_year(Request $request, $employee_no, $leave_id)
    {

        $year = $request->year;

        DB::table('employee_leave_card')
            ->where('employee_no', $employee_no)
            ->where('leave_type', $leave_id)
            ->where('year', $year)
            ->delete();

        $remaining = DB::table('employee_leave_card')
            ->where('employee_no', $employee_no)
            ->where('leave_type', $leave_id)
            ->where('year', $year)
            ->count();

        if ($remaining < 1) {
           DB::table('employee_leave_credits')
                ->where('employee_no', $employee_no)
                ->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Year Removed.',
            'redirect' => route('hris.employee.leave-card', ['employee_no' => $employee_no, 'leave_id' => $leave_id]),
        ]);
    }

    public function save_changes(Request $request, string $employee_no, int $leave_id) {

        $records = $request->input('records', []);

        foreach ($records as $year => $months) {
            foreach ($months as $month => $data) {
           
                DB::table('employee_leave_card')->updateOrInsert(
                    [
                        'leave_type' => $leave_id,
                        'employee_no' => $employee_no,
                        'year' => $year,
                        'period' => strtolower($month),
                    ],
                    [
                        'particulars' => $data['particulars'] ?? null,
                        'earned'      => (float) ($data['earned'] ?? 0),
                        'deduction'   => (float) ($data['deduction'] ?? 0),
                        'balance'     => (float) ($data['balance'] ?? 0),
                        'remarks'     => $data['remarks'] ?? null,
                    ]
                );
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Changes Saved.',
            'redirect' => '',
        ]);
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
