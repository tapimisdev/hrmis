<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use App\Services\PayrollComponentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollComponentsEmployeeController extends Controller
{
    protected $componentService;

    public function __construct(PayrollComponentService $componentService)
    {
        $this->componentService = $componentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $slug, string $year)
    {
        
        $selectedEmployee = $request->query('employee_no', null);

        $component = DB::table('payroll_components')
                    ->where('slug', $slug)
                    ->first();

        $deduction = DB::table('payroll_components_years')
                    ->where('year', $year)
                    ->where('payroll_component_id', $component->id)
                    ->first();

        if(!$component) {
            abort(404);
        }

        $url = route('payroll-employee-components.index', ['slug' => $slug, 'year' => $year]);

        if(request()->wantsJson()) {

            $deduction_id = is_null($deduction) ? now()->year : $deduction->year;
            $employees = $this->componentService->getAll($component->id, $deduction_id);
            return response()->json($employees);
        }

        return view('admin.pages.payroll-components.employees.index', compact('component', 'slug', 'year', 'url', 'selectedEmployee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $slug, string $year)
    {
        // Get the tax based on slug
        $component = DB::table('payroll_components')->where('slug', $slug)->first();
        if (!$component) {
            abort(404, 'Tax not found');
        }

        // Get the deduction for this tax
        $deduction = DB::table('payroll_components_years')
            ->where('year', $year)
            ->where('payroll_component_id', $component->id)
            ->first();
        if (!$deduction) {
            abort(404);
        }

        // Validate input
        $validatedData = $request->validate([
            'id' => 'nullable|exists:employee_payroll_components,id',
            'month' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'employee_no' => 'required|exists:employee_information,employee_no',
        ]);

        // Map month name to number
        $monthNumbers = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];

        $monthNumber = $monthNumbers[strtolower($validatedData['month'])] ?? null;
        if (!$monthNumber) {
            return response([
                'message' => 'Invalid month provided',
                'status' => 'error',
            ], 422);
        }

        DB::beginTransaction();

        try {
            DB::table('employee_payroll_components')
                ->updateOrInsert(
                    [
                        'tax_deduction_id' => $deduction->id,
                        'employee_no' => $validatedData['employee_no'],
                        'month' => $monthNumber,
                    ],
                    [
                        'amount' => $validatedData['amount'],
                        'updated_at' => now(),
                    ]
                );

            DB::commit();

            return response([
                'message' => 'Employee tax data successfully added/updated',
                'status' => 'success',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status' => 'store/update fails',
            ], 500);
        }
    }

}
