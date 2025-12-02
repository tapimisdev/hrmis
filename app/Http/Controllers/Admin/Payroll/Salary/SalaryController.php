<?php

namespace App\Http\Controllers\Admin\Payroll\Salary;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryPay\StoreRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SalaryPay\PayrollService;
use Throwable;
use Exception;

class SalaryController extends Controller
{
    protected $payroll_salary_service;

    public function __construct(PayrollService $payroll_salary_service)
    {
        $this->payroll_salary_service = $payroll_salary_service;
        $this->middleware('permission:hr.salary_payroll.view')->only(['index', 'show']);
        $this->middleware('permission:hr.salary_payroll.create')->only(['create', 'store']);
        $this->middleware('permission:hr.salary_payroll.delete')->only('destroy');
    }

    public function index()
    {
        return view('admin.pages.payroll.salary.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.salary.create');
    }

    public function show($payroll_no)
    {
        $batch_id = request()->query('batch_id');

        if (!$batch_id) {
            abort(404, 'Batch ID not provided.');
        }

        // Fetch payroll record
        $payroll = DB::table('payroll_salary')->where('payroll_no', $payroll_no)->first();

        if (!$payroll) {
            abort(404, 'Payroll not found.');
        }

        $payroll_id = $payroll->id;

        // Find the batch using Laravel's Bus helper
        $batch = Bus::findBatch($batch_id);

        if (!$batch) {
            abort(404, 'Batch not found.');
        }

        // Determine the batch status
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

        return view('admin.pages.payroll.salary.show', compact(
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
                return $this->payroll_salary_service->createPayroll($validatedData);
            });

            $payroll_id = $payroll['payroll_id'];
            $payroll_no = $payroll['payroll_no'];

            // Dispatch the payroll registry generation asynchronously
            $batch_id = $this->payroll_salary_service->generatePayrollRegistryReport($validatedData, $payroll_id);

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

    public function getBatchProgress($batch_id)
    {
        $batch = Bus::findBatch($batch_id);

        if (! $batch) {
            return response()->json([
                'error' => 'Batch not found'
            ], 404);
        }

        // Determine the batch status
        $status = 'processing';

        if ($batch->cancelled()) {
            $status = 'cancelled';
        } elseif ($batch->failedJobs > 0) {
            $status = 'failed';
        } elseif ($batch->finished()) {
            $status = 'finished';
        }

        return response()->json([
            'progress' => round($batch->progress()),
            'processedJobs' => $batch->processedJobs(),
            'totalJobs' => $batch->totalJobs,
            'pendingJobs' => $batch->pendingJobs,
            'failedJobs' => $batch->failedJobs,
            'status' => $status,
            'cancelled_at' => $batch->cancelledAt,
        ]);
    }

    public function cancelBatch($id)
    {
        $batch = Bus::findBatch($id);

        if (!$batch) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Batch not found.',
            ], 404);
        }

        // Cancel the batch
        $batch->cancel();

        return response()->json([
            'status' => 'cancelled',
            'batch_id' => $batch->id,
            'name' => $batch->name,
            'progress' => $batch->progress(),
        ]);
    }

    public function destroy($id)
    {
        $payroll = DB::table('payroll_salary')->where('id', $id)->first();

        if (!$payroll) {
            return response()->json(['message' => 'No Payroll found'], 404);
        }

        $employment_type = $payroll->employment_type_id;

        DB::beginTransaction();
        try {
            DB::table('payroll_salary_approvers')
                ->where('payroll_salary_id', $payroll->id)
                ->delete();

            $table_employee = 'payroll_salary_employee';
            $table_deduction = 'payroll_salary_employee_edeductions';

            if ($employment_type == EmploymentTypesEnum::COS->value) {
                $table_employee = 'payroll_salary_permanent_employees';
                $table_deduction = 'payroll_salary_permanents_employee_deductions';
            }

            DB::table($table_deduction)
                ->where('payroll_se_id', $payroll->id)
                ->delete();

            DB::table($table_employee)
                ->where('payroll_salary_id', $payroll->id)
                ->delete();

            // Delete the main payroll record
            DB::table('payroll_salary')
                ->where('id', $payroll->id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Payroll deleted successfully',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'destroy failed'
            ], 500);
        }
    }

}
