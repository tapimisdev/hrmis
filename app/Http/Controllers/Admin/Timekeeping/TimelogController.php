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
        $employees = DB::table('users')
        ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id') // spatie roles table
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->leftJoin('employee_information', 'users.id', '=', 'employee_information.user_id')
        ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
        ->where('roles.name', 'employee')
        ->select(
            'users.id',
            'users.email',
            'employee_information.employee_no',
            'employee_personal.firstname',
            'employee_personal.lastname'
        )
        ->get();

        if (request()->ajax()) {
            return $this->datatable($employees);
        }

        return view('admin.pages.timekeeping.timelogs.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('admin.pages.timekeeping.timelogs.show');
    }

    public function datatable($query)
    {
        return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('employee_no', function ($row) {
            return $row->employee_no ?? '-';
        })
        ->addColumn('fullname', function ($row) {
            return ($row->lastname . ', ' . $row->firstname) ?? '-';
        })
        ->addColumn('picture', function ($row) {
            // Assuming $row->picture contains the image filename or full URL
            $url = 'https://i.pinimg.com/originals/99/8f/41/998f41fc4c63e69c06b99a6e03629815.jpg';

            return '
                <div class="d-flex justify-content-center align-items-center">
                    <img src="' . $url . '" alt="Picture" class="profile-picture">
                </div>
            ';
        })
        ->addColumn('actions', function ($row) {

          return '<div class="d-flex">' .
                '<a href="' . route('daily-time-record.index', $row->id) . '" class="btn btn-outline-primary btn ms-1 my-1" title="DTR">' .
                    '<i class="fas fa-clock"></i>' .
                '</a>' .
            '</div>';

            
        })
        ->rawColumns(['actions', 'picture', 'fullname', 'employee_no'])
        ->make(true);
    }
}
