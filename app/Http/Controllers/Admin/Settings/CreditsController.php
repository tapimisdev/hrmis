<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        if (!in_array($type, ['leave', 'offset'])) {
            return redirect()->route('settings.credits.index', ['type' => 'leave']);
        }

        if ($type === 'leave') {
            $leave_types = DB::table('leaves')->get();
            return view('admin.pages.settings.credits.index', compact('type', 'leave_types'));
        }

        return view('admin.pages.settings.credits.index', compact('type'));
    }

    public function save(string $type, Request $request)
    {
        if (!in_array($type, ['leave', 'offset'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid type, please contact administrator.',
            ], 400);
        }

        $tableDictionary = [
            'leave'  => 'leave_credits',
            'offset' => 'offset_credits',
        ];

        $table = $tableDictionary[$type] ?? null;

        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unknown table for credits, please contact administrator.',
            ], 500);
        }

        $rules = [
            'credits' => ['required', 'array', 'min:1'],
            'credits.*.employee_no' => [
                'required',
                'string',
                'exists:employee_information,employee_no'
            ],
            'credits.*.previous' => ['required', 'numeric', 'min:0'],
            'credits.*.earned'   => ['required', 'numeric', 'min:0'],
            'credits.*.deducted' => ['required', 'numeric', 'min:0'],
            'credits.*.balance'  => ['required', 'numeric'],
            'credits.*.as_of' => [
                'required',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/'
            ],
            'credits.*.remarks' => ['nullable', 'string'],
        ];

        if ($type === 'leave') {
            $rules['credits.*.leave_id'] = [
                'required',
                'integer',
                'exists:leaves,id'
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $table, $type) {
            $employeeNos = collect($request->credits)
                ->pluck('employee_no')
                ->unique()
                ->values();

            // Delete existing credits for the employees (one-time migration)
            DB::table($table)
                ->whereIn('employee_no', $employeeNos)
                ->delete();

            foreach ($request->credits as $credit) {
                $employee_no = $credit['employee_no'];
                $leave_id    = $type === 'leave' ? $credit['leave_id'] : null;
                $as_of       = $credit['as_of'];

                // For migration, previous balance is 0
                $previous_balance = 0;

                $earned   = (float) $credit['earned'];
                $deducted = (float) $credit['deducted'];
                $balance  = (float) ($credit['balance'] ?? ($previous_balance + $earned - $deducted));

                $conditions = [
                    'employee_no' => $employee_no,
                    'as_of'       => $as_of,
                ];

                if ($type === 'leave') {
                    $conditions['leave_id'] = $leave_id;
                }

                DB::table($table)->updateOrInsert(
                    $conditions,
                    [
                        'previous'   => $previous_balance,
                        'earned'     => $earned,
                        'deducted'   => $deducted,
                        'balance'    => $balance,
                        'remarks'    => $credit['remarks'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return response()->json([
            'message' => ucfirst($type) . ' credits imported successfully.',
        ]);
    }
}
