<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:emp.dashboard.view')->only(['index']);
    }
    
    public function index()
    {
        return view('employee.pages.dashboard.index');
    }
}
