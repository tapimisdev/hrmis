<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeeDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Expr\FuncCall;

class DashboardController extends Controller
{
    protected $employee_dashboard_service;
    
    public function __construct(EmployeeDashboardService $employee_dashboard_service)
    {
        $this->employee_dashboard_service = $employee_dashboard_service;
        $this->middleware('permission:emp.dashboard.view')->only(['index']);
    }
    
    public function index()
    {
        return view('employee.pages.dashboard.index');
    }

    public function get_stats()
    {
        $employee_no = Auth::user()->employee_no();
        $stats = $this->employee_dashboard_service->get_stats($employee_no);
        
        return response()->json([
            'data' => $stats,
            'status' => 'success',
        ]);
    }

    public function get_pending_applications()
    {
        $user_id = Auth::user()->id;
        $pendings = $this->employee_dashboard_service->get_pendings($user_id);
        
        return response()->json([
            'data' => $pendings,
            'status' => 'success',
        ]);
    }

    public function get_announements()
    {
        $announcements = $this->employee_dashboard_service->get_announcements();
        
        return response()->json([
            'data' => $announcements,
            'status' => 'success',
        ]);
    }

}
