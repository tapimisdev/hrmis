<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CreditsController extends Controller
{

    protected $employeeService;


    public function __construct(EmployeeService $employeeService)
    {
        $this->middleware('permission:hr.credits.view')->only(['index', 'show']);
        $this->middleware('permission:hr.credits.create')->only(['create', 'store']);
        $this->employeeService = $employeeService;
    }

    public function index(string $type = 'leave')
    {
        if (!in_array($type, ['leave'])) {
            return redirect()->route('settings.credits.index', ['type' => 'leave']);
        }

        if ($type === 'leave') {
            $leave_types = DB::table('leaves')->get();
            return view('admin.pages.settings.credits.index', compact('type', 'leave_types'));
        }

        return view('admin.pages.settings.credits.index', compact('type'));
    }

    public function save(string $type, Request $request) {
        if ($type === 'leave') {
            return $this->uploadLeave($request);
        } elseif ($type === 'offset') {
            return $this->uploadOffset($request);
        }

        return response()->json([
            'message' => 'Invalid credit type.',
        ], 400);
    }

    public function uploadLeave(Request $request)
    {
        $rules = [
            'credits' => ['required', 'array', 'min:1'],
            'credits.*.employee_no' => [
                'required',
                'string',
                'exists:employee_information,employee_no'
            ],
            'credits.*.month_year' => [
                'required',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/'
            ],
            'credits.*.sick_leave' => ['required', 'numeric', 'min:0'],
            'credits.*.vacation_leave' => ['required', 'numeric', 'min:0'],
            'credits.*.remarks' => ['nullable', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request) {

            $table = 'leave_credits';

            $leaveIds = DB::table('leaves')
                ->whereIn('name', ['Sick Leave', 'Vacation Leave'])
                ->pluck('id', 'name');

            $sickLeaveId     = $leaveIds['Sick Leave'] ?? null;
            $vacationLeaveId = $leaveIds['Vacation Leave'] ?? null;

            if (!$sickLeaveId || !$vacationLeaveId) {
                throw new \Exception('Required leave types not found.');
            }

            foreach ($request->credits as $credit) {

                $employee_no = $credit['employee_no'];
                $remarks     = $credit['remarks'] ?? null;

                $importMonth = Carbon::createFromFormat('Y-m', $credit['month_year'])->startOfMonth();
                $currentMonth = Carbon::now()->startOfMonth();

                $leaveTypes = [
                    $sickLeaveId     =>  number_format($credit['sick_leave'], 3),
                    $vacationLeaveId =>  number_format($credit['vacation_leave'], 3),
                ];

                foreach ($leaveTypes as $leaveId => $amount) {

                    DB::table($table)->updateOrInsert(
                        [
                            'employee_no' => $employee_no,
                            'leave_id'    => $leaveId,
                            'as_of'       => $importMonth->format('Y-m'),
                        ],
                        [
                            'previous'   => 0,
                            'earned'     => $amount,
                            'deducted'   => 0,
                            'balance'    => $amount,
                            'remarks'    => $remarks,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $previousBalance = $amount;

                    $nextMonth = $importMonth->copy()->addMonth();

                    while ($nextMonth->lte($currentMonth)) {

                        DB::table($table)->updateOrInsert(
                            [
                                'employee_no' => $employee_no,
                                'leave_id'    => $leaveId,
                                'as_of'       => $nextMonth->format('Y-m'),
                            ],
                            [
                                'previous'   => $previousBalance,
                                'earned'     => 0,
                                'deducted'   => 0,
                                'balance'    => $previousBalance,
                                'remarks'    => '',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );

                        $nextMonth->addMonth();
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Leave credits imported with carry-over successfully.',
        ]);
    }


    public function uploadOffset(Request $request)
    {
        $rules = [
            'credits' => ['required', 'array', 'min:1'],
            'credits.*.employee_no' => [
                'required',
                'string',
                'exists:employee_information,employee_no'
            ],
            'credits.*.as_of' => [
                'required',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/'
            ],
            'credits.*.earned'   => ['required', 'numeric', 'min:0'],
            'credits.*.deducted' => ['required', 'numeric', 'min:0'],
            'credits.*.balance'  => ['required', 'numeric'],
            'credits.*.remarks'  => ['nullable', 'string'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request) {

            $table = 'offset_credits';

            $employeeNos = collect($request->credits)
                ->pluck('employee_no')
                ->unique()
                ->values();

            DB::table($table)
                ->whereIn('employee_no', $employeeNos)
                ->delete();

            foreach ($request->credits as $credit) {

                DB::table($table)->updateOrInsert(
                    [
                        'employee_no' => $credit['employee_no'],
                        'as_of'       => $credit['as_of'],
                    ],
                    [
                        'previous'   => 0,
                        'earned'     => (float) $credit['earned'],
                        'deducted'   => (float) $credit['deducted'],
                        'balance'    => (float) $credit['balance'],
                        'remarks'    => $credit['remarks'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Offset credits imported successfully.',
        ]);
    }



}
