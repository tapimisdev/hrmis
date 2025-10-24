<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\StoreSalaryRequest;
use App\Services\SalaryPayrollService;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SalaryController extends Controller
{
    protected $payroll_salary_service;

    public function __construct(SalaryPayrollService $payroll_salary_service)
    {
        $this->payroll_salary_service = $payroll_salary_service;
    }

    public function index()
    {
        return view('admin.pages.payroll.salary.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.salary.create');
    }

    public function store(StoreSalaryRequest $request)
    {
        $validatedData = $request->validated();

        Log::info('Creating payroll with data: ', $validatedData);

        try {
            // Wrap only the critical DB operation in a transaction
            $payroll_id = DB::transaction(function () use ($validatedData) {
                return $this->payroll_salary_service->createPayroll($validatedData);
            });

            // Dispatch the payroll registry generation asynchronously
            $this->payroll_salary_service->generatePayrollRegistryReport($validatedData, $payroll_id);

            return response()->json([
                'message' => 'Payroll created successfully.',
                'payroll_id' => $payroll_id
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

    }
}
