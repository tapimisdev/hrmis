<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreEarningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EarningsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DB::table('earnings')->where('is_active', true)->get();
        
        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('admin.pages.settings.earnings.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.settings.earnings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEarningRequest $request)
    {
        $validated = $request->validated();
        
        DB::beginTransaction();
        try {
            $earnings = DB::table('earnings')->insert([
                'name'          => $validated['name'],
                'first_term'    => $validated['first_term'],
                'second_term'   => $validated['second_term'],
                'is_taxable'    => $validated['is_taxable'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            
            DB::commit();
          
            return response()->json([
                'status'   => 'success',
                'message'  => 'Earnings ' . $validated['name'] . ' added successfully',
                'redirect' => '_self'
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $earnings = DB::table('earnings')->where('id', $id)->where('is_active', true)->first();
            if (!$earnings) {
                abort(404, 'Earning not found.');
            }
        return response()->json([
            'earnings' => $earnings
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $earnings = DB::table('earnings')->where('id', $id)->first();
        if (!$earnings) {
            abort(404, 'Earning not found.');
        }
        return view('admin.pages.settings.earnings.edit', compact('earnings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreEarningRequest $request, string $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $earning = DB::table('earnings')->where('id', $id)->where('is_active', true)->first();
            if (!$earning) {
                abort(404, 'Earning not found.');
            }
            DB::table('earnings')->where('id', $id)->update([
                'name'          => $validated['name'],
                'first_term'    => $validated['first_term'],
                'second_term'   => $validated['second_term'],
                'is_taxable'    => $validated['is_taxable'],
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'status'   => 'success',
                'message'  => 'Earnings ' . $validated['name'] . ' updated successfully',
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
            $earning = DB::table('earnings')->where('id', $id)->where('is_active', true)->first();
            if (!$earning) {
                abort(404, 'Earning not found.');
            }
            DB::table('earnings')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Earning has been deleted',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'An error occurred while deleting this earning.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('is_taxable', function ($row) {
                return $row->is_taxable
                ? '<span class="badge bg-success">Yes</span>'
                : '<span class="badge bg-secondary">No</span>';
            })
            ->addColumn('actions', function ($row) {

                $deleteRoute = route('earnings.destroy', [
                        'earning' => $row->id, 
                    ]);
                
                return '<div class="d-flex">' .
                    '<button data-id="' . $row->id . '" class="btn btn-outline-primary btn ms-1 my-1 show-button" title="View">' .
                        '<i class="fas fa-eye"></i>' .
                    '</button>' .
                    '<a href="' . route('earnings.edit', $row->id) . '" 
                        class="btn btn-outline-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fas fa-edit"></i>
                    </a>' .
                    '<button id="btn-delete" data-target="'.$deleteRoute.'"  class="btn btn-outline-danger btn ms-1 my-1 delete-button" title="Delete">' .
                        '<i class="fas fa-trash-alt"></i>' .
                    '</button>' .
                '</div>';
                
            })
            ->rawColumns(['actions', 'is_taxable'])
            ->make(true);
    }
}
