<?php

namespace App\Http\Controllers\Admin\Payroll\HazardPay;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use App\Services\HazardPay\PayrollService;
use App\Http\Requests\Admin\HazardPay\StoreRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class HazardPayController extends Controller
{
    
    protected $payroll_service;

    public function __construct(PayrollService $payroll_service)
    {
        $this->payroll_service = $payroll_service;
    }

    public function index()
    {
        return view('admin.pages.payroll.hazard-pay.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.hazard-pay.create');
    }

    public function show($payroll_no)
    {
        $batch_id = request()->query('batch_id');

        if (!$batch_id) {
            abort(404, 'Batch ID not provided.');
        }

        $payroll = DB::table('payroll_hazard_pay')->where('payroll_no', $payroll_no)->first();

        if (!$payroll) {
            abort(404, 'Payroll not found.');
        }

        $payroll_id = $payroll->id;

        $batch = Bus::findBatch($batch_id);

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

        // Include progress info (optional)
        $batchProgress = $batch->progress(); // 0–100 %

        $employymentEnums = collect(EmploymentTypesEnum::cases())
                            ->firstWhere('value', $payroll->employment_type_id);

        $employmentTypeName = $employymentEnums->name; // REGULAR or COS

        return view('admin.pages.payroll.hazard-pay.show', compact(
            'payroll',
            'batch_id',
            'batch',
            'batchStatus',
            'batchProgress',
            'employmentTypeName'
        ));
    }

    public function store(StoreRequest $request)
    {
        $validatedData = $request->validated();

        Log::info('Creating payroll with data: ', $validatedData);

        try {
            // Wrap only the critical DB operation in a transaction
            $payroll = DB::transaction(function () use ($validatedData) {
                return $this->payroll_service->createPayroll($validatedData);
            });

            $payroll_id = $payroll['payroll_id'];
            $payroll_no = $payroll['payroll_no'];

            // Dispatch the payroll registry generation asynchronously
            $batch_id = $this->payroll_service->createReport($validatedData, $payroll_id);

            return response()->json([
                'batch_id' => $batch_id, 
                'message' => 'Payroll created successfully.',
                'payroll_id' => $payroll_id,
                'payroll_no' => $payroll_no
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Payroll creation failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'An error occurred while processing the request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payroll = DB::table('payroll_hazard_pay')->find($id);

        if (!$payroll) {
            return response()->json(['message' => 'No Payroll found'], 404);
        }

        DB::beginTransaction();

        try {

            DB::table('payroll_hazard_pay_approvers')
                ->where('payroll_hazard_pay_id', $id)
                ->delete();

            DB::table('payroll_hazard_pay_employee')
                ->where('payroll_hazard_pay_id', $id)
                ->delete();

            DB::table('payroll_hazard_pay')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Hazard payroll deleted successfully',
                'status'  => 'success'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'destroy failed'
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

        $payroll = DB::table('payroll_hazard_pay')->where('id', $id)->first();

        if (!$payroll) {
            return response()->json([
                'message' => 'No Payroll found',
                'status'  => 'not_found'
            ], 404);
        }

        DB::beginTransaction();

        try {

            // Allowed transitions (your flow)
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
                    'status'  => 'invalid_transition'
                ], 422);
            }

            /**
             * STRICT MONTH CONSTRAINT
             * Only ONE payroll per month can be in ANY active status.
             * If one is approved, others cannot become pending, etc.
             */
            $activeStatuses = ['pending', 'approved', 'for_releasing', 'completed'];

            if (in_array($request->status, $activeStatuses)) {

                $exists = DB::table('payroll_hazard_pay')
                    ->where('month', $payroll->month)      // YYYY-MM
                    ->whereIn('status', $activeStatuses)   // any active status
                    ->where('id', '!=', $id)               // exclude current record
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => "Active payroll detected for this month.",
                        'status'  => 'month_active_conflict'
                    ], 422);
                }
            }

            DB::table('payroll_hazard_pay')
                ->where('id', $id)
                ->update([
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Payroll status updated successfully',
                'status'  => 'success',
                'data'    => [
                    'id' => $id,
                    'status' => $request->status,
                    'month' => $payroll->month
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'update failed'
            ], 500);
        }
    }

}
