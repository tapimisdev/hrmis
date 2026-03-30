<?php

namespace App\Http\Controllers\Admin\Payroll\GovernmentBonus;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GovernmentBonus\StoreRequest;
use App\Services\GovernmentBonus\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GovernmentBonusController extends Controller
{
    protected $payroll_service;

    public function __construct(PayrollService $payrollService)
    {
        $this->payroll_service = $payrollService;
    }

    public function index()
    {
        return view('admin.pages.payroll.government-bonuses.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.government-bonuses.create');
    }

    public function show($payrollNo)
    {
        $batchId = request()->query('batch_id');

        if (!$batchId) {
            abort(404, 'Batch ID not provided.');
        }

        $payroll = DB::table('payroll_government_bonus as pgb')
            ->leftJoin('government_bonus_types as gbt', 'pgb.government_bonus_type_id', '=', 'gbt.id')
            ->where('pgb.payroll_no', $payrollNo)
            ->select('pgb.*', 'gbt.name as bonus_type_name')
            ->first();

        if (!$payroll) {
            abort(404, 'Payroll not found.');
        }

        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            abort(404, 'Batch not found.');
        }

        if ($batch->finished()) {
            $batchStatus = 'completed';
        } elseif ($batch->cancelled()) {
            $batchStatus = 'cancelled';
        } elseif ($batch->failedJobs > 0) {
            $batchStatus = 'failed';
        } else {
            $batchStatus = 'processing';
        }

        $batchProgress = $batch->progress();

        $employmentEnums = collect(EmploymentTypesEnum::cases())
            ->firstWhere('value', $payroll->employment_type_id);

        $employmentTypeName = $employmentEnums?->name ?? '';

        return view('admin.pages.payroll.government-bonuses.show', compact(
            'payroll',
            'batchId',
            'batch',
            'batchStatus',
            'batchProgress',
            'employmentTypeName'
        ));
    }

    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();
        $selectedEmployees = collect(data_get($validatedData, 'employees.eligible', []))
            ->merge(data_get($validatedData, 'employees.not_eligible', []))
            ->where('selected', true);

        Log::info('Creating government bonus payroll with data: ', $validatedData);

        if ($selectedEmployees->isEmpty()) {
            throw ValidationException::withMessages([
                'employees' => ['Select at least one employee to generate the payroll.'],
            ]);
        }

        try {
            $payroll = DB::transaction(function () use ($validatedData) {
                return $this->payroll_service->createPayroll($validatedData);
            });

            $payrollId = $payroll['payroll_id'];
            $payrollNo = $payroll['payroll_no'];

            $batchId = $this->payroll_service->createReport($validatedData, $payrollId);

            if (!$batchId) {
                DB::table('payroll_government_bonus_approvers')
                    ->where('payroll_government_bonus_id', $payrollId)
                    ->delete();

                DB::table('payroll_government_bonus')
                    ->where('id', $payrollId)
                    ->delete();

                throw ValidationException::withMessages([
                    'employees' => ['Select at least one employee to generate the payroll.'],
                ]);
            }

            return response()->json([
                'batch_id' => $batchId,
                'message' => 'Payroll created successfully.',
                'payroll_id' => $payrollId,
                'payroll_no' => $payrollNo,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Government bonus payroll creation failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'An error occurred while processing the request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payroll = DB::table('payroll_government_bonus')->find($id);

        if (!$payroll) {
            return response()->json(['message' => 'No payroll found'], 404);
        }

        DB::beginTransaction();

        try {
            DB::table('payroll_government_bonus_approvers')
                ->where('payroll_government_bonus_id', $id)
                ->delete();

            DB::table('payroll_government_bonus_employee')
                ->where('payroll_government_bonus_id', $id)
                ->delete();

            DB::table('payroll_government_bonus')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Government bonus payroll deleted successfully',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'destroy failed',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    'draft',
                    'pending',
                    'approved',
                    'for_releasing',
                    'completed',
                    'cancelled',
                    'failed',
                ]),
            ],
        ]);

        $payroll = DB::table('payroll_government_bonus')->where('id', $id)->first();

        if (!$payroll) {
            return response()->json([
                'message' => 'No payroll found',
                'status' => 'not_found',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $allowedTransitions = [
                'draft' => ['pending', 'cancelled'],
                'pending' => ['draft', 'approved', 'cancelled'],
                'approved' => ['for_releasing', 'cancelled'],
                'for_releasing' => ['completed'],
                'completed' => [],
                'cancelled' => [],
                'failed' => [],
            ];

            if (
                isset($allowedTransitions[$payroll->status]) &&
                !in_array($request->status, $allowedTransitions[$payroll->status])
            ) {
                return response()->json([
                    'message' => 'Invalid status transition',
                    'status' => 'invalid_transition',
                ], 422);
            }

            $activeStatuses = ['pending', 'approved', 'for_releasing', 'completed'];

            if (in_array($request->status, $activeStatuses)) {
                $exists = DB::table('payroll_government_bonus')
                    ->where('month', $payroll->month)
                    ->where('government_bonus_type_id', $payroll->government_bonus_type_id)
                    ->whereIn('status', $activeStatuses)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'Active payroll detected for this month and bonus type.',
                        'status' => 'month_active_conflict',
                    ], 422);
                }
            }

            DB::table('payroll_government_bonus')
                ->where('id', $id)
                ->update([
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Payroll status updated successfully',
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'update failed',
            ], 500);
        }
    }

    public function deleteEmployeePayroll($id, $employmentType)
    {
        if (!in_array($employmentType, ['REGULAR', 'COS'], true)) {
            return response()->json([
                'message' => 'Invalid employment type.',
            ], 400);
        }

        $deleted = DB::table('payroll_government_bonus_employee')
            ->where('id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Employee payroll not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Employee payroll deleted successfully.',
        ]);
    }
}
