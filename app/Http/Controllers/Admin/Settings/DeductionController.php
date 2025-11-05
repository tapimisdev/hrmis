<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreDeductionsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DeductionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {        
        if (request()->ajax()) {
            $query = DB::table('deductions')->where('is_active', true)->get();
            return $this->datatable($query);
        }

        return view('admin.pages.settings.deductions.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.deductions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeductionsRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            
            $deduction = DB::table('deductions')->insert([
                'name'          => $validated['name'],
                'first_term'    => $validated['first_term'],
                'second_term'   => $validated['second_term'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Deduction ' . $validated['name'] . ' added successfully',
                'redirect' => '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'   => 'error',
                'message'  => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $deduction = DB::table('deductions')->where('id', $id)->where('is_active', true)->first();
        
        if (!$deduction) {
            abort(404, 'Deduction not found.');
        }

        return response()->json([
            'deduction' => $deduction
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $deduction = DB::table('deductions')->where('id', $id)->first();
        if (!$deduction) {
            abort(404, 'Deduction not found.');
        }
        return view('admin.pages.settings.deductions.edit', compact('deduction'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreDeductionsRequest $request, string $id)
    {
        $validated = $request->validated();
        
        DB::beginTransaction();

        try {

            DB::table('deductions')->where('id', $id)->update([
                'name'          => $validated['name'],
                'first_term'    => $validated['first_term'],
                'second_term'   => $validated['second_term'],
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Deduction ' . $validated['name'] . ' updated successfully',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'status'   => 'error',
                'message'  => 'Error: ' . $e->getMessage(),
            ]);
        }        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        DB::beginTransaction();

        try {

            $deduction = DB::table('deductions')->where('id', $id)->where('is_active', true)->first();
            
            if (!$deduction) {
                abort(404, 'Deduction not found.');
            }
            
            DB::table('deductions')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Deduction has been deleted',
                'redirect' => ''
            ]);
           
        } catch (\Exception $e) {

            DB::rollback();

            return response()->json([
                'message' => 'An error occurred while deleting this deduction.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {

                $deleteRoute = route('deductions.destroy', [
                        'deduction' => $row->id, 
                    ]);

                return '<div class="d-flex">' .
                    '<button data-id="' . $row->id . '" class="btn btn-primary btn ms-1 my-1 show-button" title="View">' .
                        '<i class="fas fa-eye"></i>' .
                    '</button>' .
                    '<a href="' . route('deductions.edit', $row->id) . '" 
                        class="btn btn-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fas fa-edit"></i>
                    </a>' .
                    '<button id="btn-delete" data-target="'.$deleteRoute.'" class="btn btn-danger btn ms-1 my-1" title="Delete">' .
                        '<i class="fas fa-trash-alt"></i>' .
                    '</button>' .
                '</div>';
                
            })
            ->rawColumns(['actions', 'is_taxable'])
            ->make(true);
    }
}
