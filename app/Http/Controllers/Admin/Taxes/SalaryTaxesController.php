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
                ->select('year')
                ->distinct()
                ->orderBy('year', 'asc')
                ->get();

            return $this->datatable($yearsFromDb);
        }

        return view('admin.pages.taxes.salary-taxes.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateYear = $request->validate([
            'year' => 'required|integer|unique:tax_salary,year'
        ]);

        try {
            $tax_salary_id = DB::table('tax_salary')
                            ->insertGetId([
                                'year' => $validateYear['year']
                            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Year successfully added',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status'  => 'store failed'
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
               return '
                <div class="d-block d-md-flex gap-2 justify-content-start">
                    <a href="' . route('employment-types.edit', $row->year) . '" 
                        class="btn btn-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                   
                </div>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
