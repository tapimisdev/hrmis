<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CheckInOutRequest;
use App\Services\TimelogsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CheckInOutController extends Controller
{

    protected $timelogsServices;
    protected $user_id;
    protected $employee_no;

    public function __construct(TimelogsServices $timelogsServices)
    {
        $this->timelogsServices = $timelogsServices;
        $this->user_id = auth()->user()->id;
        $this->employee_no = DB::table('employees');
    }

    public function index()
    {
        $query = $this->timelogsServices->getTimeLogs($this->user_id);

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.timelogs.checkin-out.index');
    }

    public function create()
    {
        return view('employee.pages.timelogs.checkin-out.create');
    }

    public function store(CheckInOutRequest $request)
    {
        $validatedData = $request->validated();

        $timelog = DB::table('timelogs')->insert([
            'user_id'     => $this->user_id,
            'employee_no' => $validatedData['employee_no'] ?? null,
            'date_time'   => $validatedData['date_time'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Time log entry recorded successfully.',
            'data'    => $timelog,
        ], 201);
        
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date', function ($row) {
                return \Carbon\Carbon::parse($row['date'])->format('F d, Y (l)');
            })
            ->addColumn('time_in', function ($row) {
                return \Carbon\Carbon::parse($row['time_in'])->format('h:i A');
            })
            ->addColumn('break_out', function ($row) {
                return \Carbon\Carbon::parse($row['break_out'])->format('h:i A'); 
            })
            ->addColumn('break_in', function ($row) {
                return \Carbon\Carbon::parse($row['break_in'])->format('h:i A');  
            })
            ->addColumn('time_out', function ($row) {
                return \Carbon\Carbon::parse($row['time_out'])->format('h:i A');  
            })
            ->rawColumns(['date', 'time_in', 'break_out', 'break_in', 'time_out'])
            ->make(true);
    }
}
