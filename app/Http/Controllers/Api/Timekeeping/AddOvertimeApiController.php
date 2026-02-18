<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Admin\Services\ApplicationController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreAtroRequest;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddOvertimeApiController extends Controller
{
    protected $timelog_service;
    protected $employee_service;

    protected $user_id;

    public function __construct(TimelogsServices $timelog_service, EmployeeService $employee_service)
    {
        $this->timelog_service = $timelog_service;
        $this->employee_service = $employee_service;

        $this->middleware('permission:hr.timekeeping.add_overtime')->only(['store', 'show']);
    }

    public function show(Request $request)
    {

        $employee_no = $request->input('user_id');
        $data = DB::table('overtime_applications')
                ->where('date', $request->input('date'))
                ->where('employee_no', $employee_no)
                ->first();
    
        return response(['overtime' => $data, 'message' => 'show success'], 200);
    }

    public function store(StoreAtroRequest $request)
    {
        $validatedData = $request->validated();

        $employee_no = $validatedData['user_id'];
        $user_id = $this->employee_service->getEmployeeUserId($employee_no);
        
        DB::beginTransaction();
        try {
            $atro = DB::table('overtime_applications')
                    ->insert([
                        'application_no' =>  generateApplicationNo('overtime_applications', 'PSL'),
                        'user_id' => $user_id,
                        'employee_no' => $employee_no,
                        'date' => $validatedData['date'],
                        'start_time' => $validatedData['start_time'],
                        'end_time' => $validatedData['end_time'],
                        'total_hours' => $validatedData['total_hours'],
                        'reason' => $validatedData['reason'],
                        'status' => 'approved',
                        'approver_id' => Auth::id(),
                        'approved_at' => now(),
                        'level' => null,
                        'levels' => null,
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ]);
            DB::commit();
            return response(['data' => $atro, 'status' => 'store success'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage(), 'status' => 'store failed'], 500);
        }
    }
}
