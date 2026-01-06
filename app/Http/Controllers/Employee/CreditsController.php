<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeeDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreditsController extends Controller
{
    public function leave(Request $request)
    {        
        return view('employee.pages.credits.leave');
    }

    public function offset(Request $request)
    {        
        return view('employee.pages.credits.offset');
    }

}
