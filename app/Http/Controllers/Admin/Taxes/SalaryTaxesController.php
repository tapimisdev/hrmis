<?php

namespace App\Http\Controllers\Admin\Taxes;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class SalaryTaxesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {

            // Get distinct years from DB as integers
            $yearsFromDb = DB::table('tax_salary')
                ->distinct()
                ->orderBy('year', 'asc')
                ->get();

            return response(['data' => $yearsFromDb, 'message' => 'get data', 'status' => 'success']);
        }

        return view('admin.pages.taxes.salary-taxes.index');
    }

    public function show(string $id) {
        $tax = DB::table('tax_salary')->find($id);
        return response(['data' => $tax, 'message' => 'get data', 'status' => 'success']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateYear = $request->validate([
            'year' => [
                'required',
                'integer',
                'unique:tax_salary,year',
                'digits:4', // ensures exactly 4 digits
                'max:' . (now()->year + 3), // cannot exceed current year + 3
                'min:' . now()->year, // optional: cannot be less than current year
            ],
        ]);

        try {
            $tax_salary_id = DB::table('tax_salary')
                                ->insertGetId([
                                    'year' => $validateYear['year']
                                ]);

            $url = route('tax.salary.employees.index', $tax_salary_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Year successfully added',
                'redirect' => $url
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status'  => 'store failed'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validateYear = $request->validate([
            'year' => [
                'required',
                'integer',
                'unique:tax_salary,year,' . $id,
                'digits:4',
            ],
        ]);

        try {
            DB::table('tax_salary')
                ->where('id', $id)
                ->update(['year' => $validateYear['year']]);

            return response()->json([
                'status' => 'success',
                'message' => 'Year successfully updated',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
                'status'  => 'update failed'
            ], 500);
        }
    }

    public function edit($id)
    {
        $tax = DB::table('tax_salary')->find($id);
        return response(['data' => $tax, 'message' => 'get data', 'status' => 'success']);
    }

}
