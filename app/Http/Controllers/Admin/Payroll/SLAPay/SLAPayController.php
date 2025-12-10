<?php

namespace App\Http\Controllers\Admin\Payroll\SLAPay;

use App\Http\Controllers\Controller;
use App\Enums\EmploymentTypesEnum;
use App\Models\User;
use App\Notifications\PayrollBatchCompleted;
use App\Services\SLAPay\PayrollService;
use App\Http\Requests\Admin\SLAPay\StoreRequest;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class SLAPayController extends Controller
{
    
    protected $payroll_sla_service;

    public function __construct(PayrollService $payroll_sla_service)
    {
        $this->payroll_sla_service = $payroll_sla_service;
    }

    public function index()
    {
        return view('admin.pages.payroll.sla-pay.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.sla-pay.create');
    }

    public function show($payroll_no)
    {
        $batch_id = request()->query('batch_id');

        if (!$batch_id) {
            abort(404, 'Batch ID not provided.');
        }

        $payroll = DB::table('payroll_sla_pay')->where('payroll_no', $payroll_no)->first();

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

        return view('admin.pages.payroll.sla-pay.show', compact(
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
                return $this->payroll_sla_service->createPayroll($validatedData);
            });

            $payroll_id = $payroll['payroll_id'];
            $payroll_no = $payroll['payroll_no'];

            // Dispatch the payroll registry generation asynchronously
            $batch_id = $this->payroll_sla_service->createReport($validatedData, $payroll_id);

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


}
