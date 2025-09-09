<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TimelogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = User::role('employee')
            ->with('employeeInformation')
            ->get();

        if (request()->ajax()) {
            return $this->datatable($employees);
        }

        return view('admin.pages.timekeeping.timelogs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('admin.pages.timekeeping.timelogs.show');
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
        ->addColumn('picture', function ($row) {
            // Assuming $row->picture contains the image filename or full URL
            $url = 'https://images.pexels.com/photos/20804701/pexels-photo-20804701.jpeg?cs=srgb&dl=pexels-agrosales-20804701.jpg&fm=jpg';

            return '
                <div class="d-flex justify-content-center align-items-center">
                    <img src="' . $url . '" alt="Picture" class="profile-picture">
                </div>
            ';
        })
        ->addColumn('actions', function ($row) {

          return '<div class="d-flex">' .
                '<a href="' . route('timelogs.show', $row->id) . '" class="btn btn-outline-primary btn ms-1 my-1" title="DTR">' .
                    '<i class="fas fa-clock"></i>' .
                '</a>' .
            '</div>';

            
        })
        ->rawColumns(['actions', 'picture'])
        ->make(true);
    }
}
