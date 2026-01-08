<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeeDashboardService;
use App\Services\EmployeeService;
use App\Services\GenerateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CreditsController extends Controller
{

    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function leave(Request $request)
    {        

        $user = Auth::user();
        $user = User::with('employeeInformation')->findOrFail($user->id);
        $employee_no = $user->employeeInformation->employee_no;

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);
        $leaves = $this->employeeService->getLeaveTypes($employee_no);
        $data = [];

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }


        if($leaves['status'] == 'eligible') {

            $leaveTypes = $leaves['data'];
            $monthYear = now()->format('Y-m'); 
            
            foreach($leaveTypes as $types) {

                $credits = $this->employeeService->getLeaveCredits($employee_no, $types->leave_id, false);
                $latestCredits = $this->employeeService->getLeaveCreditsByMonthYear($employee_no, $types->leave_id, true);
                $currBal = $credits->filter(function($q) use ($monthYear) {
                    return ($q->as_of ?? '') === $monthYear;
                })->values()->pluck('balance')->first() ?? 0;


                $data[] = [
                    'leave' => $types,
                    'credits' => $credits,
                    'latestCredits' => $latestCredits,
                    'currentMonthBalance' => $currBal 
                ];    
            } 
            
            return view('employee.pages.credits.leave', compact('isExists', 'data'));

        }

        return view('employee.pages.credits.leave', compact('data'));

    }

    public function offset(Request $request)
    {        

        $user = Auth::user();
        $user = User::with('employeeInformation')->findOrFail($user->id);
        $employee_no = $user->employeeInformation->employee_no;
        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        $credits = $this->employeeService->getOffsetCredits($employee_no, false);
        $latestCredits = $this->employeeService->getOffsetCredits($employee_no, true);

        return view('employee.pages.credits.offset', compact('credits', 'isExists', 'latestCredits'));
    }

}
