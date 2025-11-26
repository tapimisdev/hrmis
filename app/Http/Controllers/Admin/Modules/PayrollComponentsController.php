<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PayrollComponentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $slug)
    {
        $component = DB::table('payroll_components')
                ->where('slug', $slug)
                ->first();

        if ($request->ajax()) {

            $yearsFromDb = DB::table('tax_years')
                ->where('payroll_component_id', $component->id)
                ->distinct()
                ->orderBy('year', 'asc')
                ->get();

            return response(['data' => $yearsFromDb, 'message' => 'get data', 'status' => 'success']);
        }

        return view('admin.pages.payroll-components.index', compact('slug', 'component'));
    }

    public function show(string $slug, string $year_id) {

        $component = DB::table('payroll_components')
            ->where('slug', $slug)
            ->first();

        if(!$component) {
            abort(404);
        }

        $component = DB::table('tax_years')
            ->where('payroll_component_id', $component->id)
            ->where('id', $year_id)
            ->first();
        return response(['data' => $component, 'message' => 'get data', 'status' => 'success']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validateYear = $request->validate([
            'slug' => 'required|string|exists:payroll_components,slug',
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
            
            $pc = DB::table('payroll_components')
                        ->where('slug', $validateYear['slug'])
                        ->first();

            $year = DB::table('tax_years')->insertGetId([
                        'payroll_component_id' => $pc->id,
                        'year' => $validateYear['year'],
                        'updated_at' => Carbon::now(),
                        'created_at' => Carbon::now(),
                    ]);

            $url = route('tax.employees.index', [
                'slug' => $validateYear['slug'],
                'year' => $validateYear['year']
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

    public function update(Request $request, string $slug, int $year)
    {

        $validateYear = $request->validate([
            'year' => [
                'required',
                'integer',
                'digits:4',
                'max:' . (now()->year + 3),
                'min:' . now()->year,
            ],
            'originalYear' => 'required',
        ]);

        DB::beginTransaction();

        try {

            // Get tax by slug (same as store)
            $component = DB::table('payroll_components')
                        ->where('slug', $slug)
                        ->first();

            if (!$component) {
                abort(404, 'Tax not found');
            }

            // Get tax_year record
            $componentYear = DB::table('tax_years')
                        ->where('payroll_component_id', $component->id)
                        ->where('year', $validateYear['originalYear'])
                        ->first();

            if (!$componentYear) {
                abort(404, 'Year not found for this tax');
            }

            // Update the year
            DB::table('tax_years')
                ->where('id', $componentYear->id)
                ->where('year', $validateYear['originalYear'])
                ->update([
                    'year' => $validateYear['year'],
                    'updated_at' => now(),
                ]);

            // Updated redirect (same style as store)
            $url = route('tax.employees.index', [
                'slug' => $slug,
                'year' => $year
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
        $component = DB::table('tax_salary')->find($id);
        return response(['data' => $component, 'message' => 'get data', 'status' => 'success']);
    }

}
