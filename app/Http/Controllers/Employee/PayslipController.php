<?php

namespace App\Http\Controllers\Employee;

use App\DTO\PayslipData;
use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Services\Exports\EmployeePayslipService;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayslipController extends Controller
{

    protected $payslipService;
    protected $employeePayslipService;

    public function __construct(PayslipService $payslipService, EmployeePayslipService $employeePayslipService)
    {   
        $this->payslipService = $payslipService;
        $this->employeePayslipService = $employeePayslipService;
    }

    public function index()
    {
        return view('employee.pages.payslip.index');
    }
    
    public function fetch_payslip(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'cutoff' => 'nullable|string|in:first_cutoff,second_cutoff',
        ]);

        $data = new PayslipData(
            employee_no: $request->user()->employee_no(),
            month: $request->input('month'),
            year: $request->input('year'),
            employee_type: $request->user()->employment_type_id(),
            cutoff: $request->input('cutoff')
        );

        $payslip = $this->payslipService->generatePayslip($data);

        return response()->json($payslip);
    }

    public function download(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:' . date('Y'),
            'cutoff' => 'nullable|string|in:first_cutoff,second_cutoff',
        ]);

        $employeeType = $request->user()->employment_type_id();

        if ((string) $employeeType === EmploymentTypesEnum::REGULAR->value) {
            return response()->json([
                'message' => 'Plantilla payslip is for development.',
            ], 422);
        }

        $data = new PayslipData(
            employee_no: $request->user()->employee_no(),
            month: $request->input('month'),
            year: $request->input('year'),
            employee_type: $employeeType,
            cutoff: $request->input('cutoff')
        );

        return $this->employeePayslipService->download($data);
    }
}
