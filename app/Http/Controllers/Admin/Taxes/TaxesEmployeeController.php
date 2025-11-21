<?php

namespace App\Http\Controllers\Admin\Taxes;

use App\Http\Controllers\Controller;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxesEmployeeController extends Controller
{
    protected $tax_service;

    public function __construct(TaxService $tax_service)
    {
        $this->tax_service = $tax_service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(string $slug, int $id)
    {
        $tax = DB::table('taxes')
                    ->where('slug', $slug)
                    ->first();

        $deduction = DB::table('tax_deductions')
                    ->where('id', $id)
                    ->where('tax_id', $tax->id)
                    ->first();
                    
        if(!$tax) {
            abort(404);
        }

        $url = route('tax.employees.index', ['slug' => $slug, 'id' => $id]);

        if(request()->wantsJson()) {
            $employees = $this->tax_service->getAll($tax->id, $deduction->year);
            return response()->json($employees);
        }

        return view('admin.pages.taxes.employees.index', compact('tax', 'slug', 'id', 'url'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $slug, int $id)
    {
        // Get the tax based on slug
        $tax = DB::table('taxes')->where('slug', $slug)->first();
        if (!$tax) {
            abort(404, 'Tax not found');
        }

        // Get the deduction for this tax
        $deduction = DB::table('tax_deductions')
            ->where('id', $id)
            ->where('tax_id', $tax->id)
            ->first();
        if (!$deduction) {
            abort(404, 'Deduction not found');
        }

        // Validate input
        $validatedData = $request->validate([
            'id' => 'nullable|exists:employee_taxes,id',
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
            DB::table('employee_taxes')
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
