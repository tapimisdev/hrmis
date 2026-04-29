<?php

namespace App\Http\Controllers\Admin\Payroll\Salary;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalaryPay\StoreRequest;
use App\Services\SalaryPay\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SalaryController extends Controller
{
    protected $payroll_service;

    public function __construct(
        PayrollService $payroll_service
    ) {
        $this->payroll_service = $payroll_service;
        $this->middleware('permission:hr.salary_payroll.view')->only(['index', 'show']);
        $this->middleware('permission:hr.salary_payroll.create')->only(['create', 'store']);
        $this->middleware('permission:hr.salary_payroll.delete')->only('destroy');
    }

    public function index()
    {
        return view('admin.pages.payroll.salary-pay.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.salary-pay.create');
    }

    public function show($payroll_no)
    {
        $batch_id = request()->query('batch_id');

        $payroll = DB::table('payroll_salary')->where('payroll_no', $payroll_no)->first();

        if (!$payroll) {
            abort(404, 'Payroll not found.');
        }

        $batch_id = $batch_id ?: $payroll->batch_id;
        $batch = null;
        $batchProgress = 100;
        $batchStatus = $payroll->status === 'failed' ? 'failed' : 'completed';

        if ($batch_id) {
            $batch = Bus::findBatch($batch_id);

            if ($batch) {
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
            }
        }

        $employymentEnums = collect(EmploymentTypesEnum::cases())
            ->firstWhere('value', $payroll->employment_type_id);

        $employmentTypeName = $employymentEnums->name;

        return view('admin.pages.payroll.salary-pay.show', compact(
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
            $payroll = DB::transaction(function () use ($validatedData) {
                return $this->payroll_service->createPayroll($validatedData);
            });

            $payroll_id = $payroll['payroll_id'];
            $payroll_no = $payroll['payroll_no'];
            $batch_id = $this->payroll_service->generatePayrollRegistryReport($validatedData, $payroll_id);

            return response()->json([
                'batch_id' => $batch_id,
                'message' => 'Payroll created successfully.',
                'payroll_id' => $payroll_id,
                'payroll_no' => $payroll_no,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Payroll creation failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'An error occurred while processing the request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $payroll = DB::table('payroll_salary')->find($id);

        if (!$payroll) {
            return response()->json(['message' => 'No Payroll found'], 404);
        }

        $isCos = $payroll->employment_type_id == EmploymentTypesEnum::COS->value;

        DB::beginTransaction();

        try {
            DB::table('payroll_salary_approvers')
                ->where('payroll_salary_id', $id)
                ->delete();

            if (!$isCos) {
                $permanentEmployeeIds = DB::table('payroll_salary_permanent_employees')
                    ->where('payroll_salary_id', $id)
                    ->pluck('id');

                if ($permanentEmployeeIds->count() > 0) {
                    DB::table('payroll_salary_permanents_employee_deductions')
                        ->whereIn('pspe_id', $permanentEmployeeIds)
                        ->delete();

                    DB::table('payroll_salary_permanent_employees')
                        ->whereIn('id', $permanentEmployeeIds)
                        ->delete();
                }
            } else {
                $employeeIds = DB::table('payroll_salary_employee')
                    ->where('payroll_salary_id', $id)
                    ->pluck('id');

                if ($employeeIds->count() > 0) {
                    DB::table('payroll_salary_employee_earnings')
                        ->whereIn('payroll_se_id', $employeeIds)
                        ->delete();

                    DB::table('payroll_salary_employee_edeductions')
                        ->whereIn('payroll_se_id', $employeeIds)
                        ->delete();

                    DB::table('payroll_salary_employee')
                        ->whereIn('id', $employeeIds)
                        ->delete();
                }
            }

            DB::table('payroll_salary')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'message' => 'Salary payroll deleted successfully',
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

        $payroll = DB::table('payroll_salary')->where('id', $id)->first();

        if (!$payroll) {
            return response()->json([
                'message' => 'No Payroll found',
                'status' => 'not_found',
            ], 404);
        }

        $oldStatus = $payroll->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return response()->json([
                'message' => 'Status is already set',
                'status' => 'no_change',
                'data' => ['id' => $id, 'status' => $newStatus],
            ]);
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
                isset($allowedTransitions[$oldStatus]) &&
                !in_array($newStatus, $allowedTransitions[$oldStatus], true)
            ) {
                return response()->json([
                    'message' => 'Invalid status transition',
                    'status' => 'invalid_transition',
                ], 422);
            }

            DB::table('payroll_salary')
                ->where('id', $id)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'message' => 'Payroll status updated successfully',
                'status' => 'success',
                'data' => [
                    'id' => $id,
                    'from' => $oldStatus,
                    'to' => $newStatus,
                    'month' => date('m', strtotime($payroll->payroll_date)) . ' ' . date('Y', strtotime($payroll->payroll_date)),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'update failed',
            ], 500);
        }
    }

    public function deleteEmployeePayroll($id, $employment_type)
    {
        $table = match ($employment_type) {
            'REGULAR' => 'payroll_salary_permanent_employees',
            'COS' => 'payroll_salary_employee',
            default => null,
        };

        if (!$table) {
            return response()->json([
                'message' => 'Invalid employment type.',
            ], 400);
        }

        $exists = DB::table($table)->where('id', $id)->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'Employee payroll not found.',
            ], 404);
        }

        DB::table($table)->where('id', $id)->delete();

        return response()->json([
            'message' => 'Employee payroll deleted successfully.',
        ]);
    }

    public function import_save(Request $request) {}
}
