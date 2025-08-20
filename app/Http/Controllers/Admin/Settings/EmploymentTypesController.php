<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmploymentTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if(request()->ajax()) {
            $query = DB::table('employment_types')
                ->get();
            
            return $this->datatable($query);
        }

        return view('admin.pages.settings.employment-types.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.employment-types.form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::table('employment_types')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('employment-types.index')->with('success', 'Employment Type created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('code', function ($row) {
                return $row->code;
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })
            ->editColumn('date_created', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
               return '
                <a href="' . route('employment-types.edit', $row->id) . '" 
                class="btn btn-outline-secondary btn ms-1" 
                title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
