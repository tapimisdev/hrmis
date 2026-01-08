<?php

namespace App\Http\Controllers\Employee;

use App\DTO\PayslipData;
use App\Http\Controllers\Controller;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayslipController extends Controller
{

    protected $payslipService;

    public function __construct(PayslipService $payslipService)
    {   
        $this->payslipService = $payslipService;
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
        ]);

        $data = new PayslipData(
            employee_no: $request->user()->employee_no(),
            month: $request->input('month'),
            year: $request->input('year'),
            employee_type: $request->user()->employment_type_id()
        );

        $payslip = $this->payslipService->generatePayslip($data);

        return response()->json($payslip);
    }
}
