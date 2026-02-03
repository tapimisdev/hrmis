<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class TimelogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.timekeeping.view')->only(['index', 'show']);
    }

    public function index(Request $request)
    {        
        if (request()->ajax()) {
            $employees = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id') // spatie roles table
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->leftJoin('employee_information', 'users.id', '=', 'employee_information.user_id')
                ->leftJoin('employee_shift_work_schedule', 'employee_information.employee_no', '=', 'employee_shift_work_schedule.employee_no')
                ->leftJoin('employee_organization', 'employee_information.employee_no', '=', 'employee_organization.employee_no')
                ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                ->leftJoin('positions', 'employee_organization.position_id', '=', 'positions.id')
                ->leftJoin('work_schedule', 'employee_shift_work_schedule.work_schedule_id', '=', 'work_schedule.id')
                ->leftJoin('units', 'employee_organization.unit_id', '=', 'units.id')
                ->leftJoin('divisions', 'employee_organization.division_id', '=', 'divisions.id')
                ->leftJoin('shifts', 'employee_shift_work_schedule.shift_id', '=', 'shifts.id')
                ->leftJoin('employment_types', 'employee_organization.employment_type_id', '=', 'employment_types.id')
                ->whereIn('roles.name', ['emp_contractual', 'emp_regular'])
                ->select(
                    'users.id',
                    'users.email',
                    'employee_information.employee_no',
                    'employee_personal.firstname',
                    'employee_personal.lastname',
                    'employee_personal.profile',
                    'positions.name as position_name',
                    'work_schedule.name as work_schedule_name',
                    'divisions.name as division_name',
                    'units.name as units_name',
                    'shifts.name as shift_name',
                    'employment_types.name as employment_type'
                );
            
            if ($request->filled('type')) {
                $employees->where('employment_types.name', $request->type);
            }

            if ($request->filled('position')) {
                $employees->where('positions.name', $request->position);
            }

            if ($request->filled('division')) {
                $employees->where('divisions.name', $request->division);
            }

            if ($request->filled('unit')) {
                $employees->where('units.name', $request->unit);
            }

            return $this->datatable($employees->get());
        }

        $positions = DB::table('positions')->get();
        $types = DB::table('employment_types')->get();
        $divisions = DB::table('divisions')->get();
        $units = DB::table('units')->get();

        return view('admin.pages.timekeeping.timelogs.index', compact('positions', 'types', 'units', 'divisions'));
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
            return ($row->firstname . ' ' . $row->lastname) ?? '-';
        })
        ->addColumn('picture', function ($row) {
            $profile = $row->profile ?? null;

            if ($profile) {
                $profile = Storage::url('public/users/' . $row->employee_no . '/profile-image/' . $row->profile);
            } else {
                $profile = 'https://ui-avatars.com/api/?name='
                    . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
                    . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
            }

            return '<div style="margin: auto; width: 50px; height: 50px; border:1px solid #ccc; border-radius:8px; 
                                display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                        <img src="' . $profile . '" 
                            alt="Avatar of ' . e(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')) . '" 
                            style="width:100%; height:100%; object-fit:cover;">
                    </div>';
        })
        ->addColumn('actions', function ($row) {

          return '<div class="text-nowrap px-3">' .
                '<a href="' . route('daily-time-record.index', $row->employee_no) . '" class="btn btn-primary btn ms-1 my-1" title="DTR">' .
                    '<i class="fas fa-clock me-1"></i> View DTR'  .
                '</a>' .
            '</div>';
            
        })
        ->rawColumns(['actions', 'picture', 'fullname', 'employee_no'])
        ->make(true);
    }
}
