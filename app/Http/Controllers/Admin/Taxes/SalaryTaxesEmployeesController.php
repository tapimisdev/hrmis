<?php

namespace App\Http\Controllers\Admin\Taxes;

use App\Http\Controllers\Controller;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryTaxesEmployeesController extends Controller
{
    protected $tax_service;
    protected $parent_table = 'tax_salary';
    protected $this_table = 'tax_salary_employee';

    public function __construct(TaxService $tax_service)
    {
        $this->tax_service = $tax_service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index($tax_salary_id)
    {
        $tax_salary = DB::table($this->parent_table)
                            ->where('id', $tax_salary_id)
                            ->first();

        if(!$tax_salary) {
            abort(404);
        }

        $url = route('tax.salary.employees.index', ['salary_tax' => $tax_salary_id]);

        if(request()->wantsJson()) {
            $employees = $this->tax_service->getAll($this->this_table, $this->parent_table, $tax_salary_id);
            return response()->json($employees);
        }

        return view('admin.pages.taxes.salary-taxes.employees.index', compact('tax_salary', 'url'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $tax_salary_id)
    {
        $validatedData = $request->validate([
            'id' => 'nullable|exists:tax_salary_employee,id',
            'month' => 'required|String',
            'amount' => 'required|numeric|min:0',
            'employee_no' => 'required|exists:employee_information,employee_no',
        ]);

        $monthNumbers = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];

        $monthNumber = $monthNumbers[strtolower($validatedData['month'])] ?? null;

        DB::beginTransaction();

        try {

            DB::table($this->this_table)
            ->updateOrInsert(
                [
                    'tax_salary_id' => $tax_salary_id,
                    'employee_no'   => $validatedData['employee_no'],
                    'month'         => $monthNumber,
                ],
                [
                    'amount' => $validatedData['amount'],
                    'updated_at' => now(),
                ]
            );

            DB::commit();

            return response([
                'message' => 'Your data is succesfully added/update',
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(), 
                'status' => 'store/update fails'
            ], 500);
        }
    }
}
