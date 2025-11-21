<?php

namespace App\Http\Controllers\Admin\Taxes;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class TaxesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $slug)
    {
        if ($request->ajax()) {
            $tax = DB::table('taxes')
                ->where('slug', $slug)
                ->first();

            $yearsFromDb = DB::table('tax_deductions')
                ->where('tax_id', $tax->id)
                ->distinct()
                ->orderBy('year', 'asc')
                ->get();

            return response(['data' => $yearsFromDb, 'message' => 'get data', 'status' => 'success']);
        }

        return view('admin.pages.taxes.index', compact('slug'));
    }

    public function show(string $slug, string $year_id) {

        $tax = DB::table('taxes')
            ->where('slug', $slug)
            ->first();

        if(!$tax) {
            abort(404);
        }

        $tax = DB::table('tax_deductions')
            ->where('tax_id', $tax->id)
            ->where('id', $year_id)
            ->first();
        return response(['data' => $tax, 'message' => 'get data', 'status' => 'success']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validateYear = $request->validate([
            'slug' => 'required|string|exists:taxes,slug',
            'year' => [
                'required',
                'integer',
                'digits:4', 
                'max:' . (now()->year + 3),
                'min:' . now()->year,
            ],
        ]);

        DB::beginTransaction();

        try {
            
            $tax = DB::table('taxes')
                        ->where('slug', $validateYear['slug'])
                        ->first();

            $year = DB::table('tax_deductions')->insertGetId([
                        'tax_id' => $tax->id,
                        'year' => $validateYear['year'],
                        'updated_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                    ]);

            $url = route('tax.employees.index', [
                'slug' => $validateYear['slug'],
                'id' => $tax->id
            ]);

            DB::commit();

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

    public function update(Request $request, string $slug, int $id)
    {
        $validateYear = $request->validate([
            'year' => [
                'required',
                'integer',
                'digits:4',
                'max:' . (now()->year + 3),
                'min:' . now()->year,
            ],
        ]);

        DB::beginTransaction();

        try {
            $tax = DB::table('taxes')->where('slug', $slug)->first();
            if (!$tax) {
                abort(404, 'Tax not found');
            }

            $deduction = DB::table('tax_deductions')
                ->where('id', $id)
                ->where('tax_id', $tax->id)
                ->first();

            if (!$deduction) {
                abort(404, 'Deduction not found');
            }

            DB::table('tax_deductions')
                ->where('id', $deduction->id)
                ->update([
                    'year' => $validateYear['year'],
                    'updated_at' => now(),
                ]);

            $url = route('tax.employees.index', [
                'slug' => $slug,
                'id' => $deduction->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Year successfully updated',
                'redirect' => $url
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status' => 'update failed'
            ], 500);
        }
    }


    public function edit($id)
    {
        $tax = DB::table('tax_salary')->find($id);
        return response(['data' => $tax, 'message' => 'get data', 'status' => 'success']);
    }

}
