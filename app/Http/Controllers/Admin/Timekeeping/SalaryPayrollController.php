<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalaryPayrollController extends Controller
{
    public function index()
    {
        return view('admin.pages.payroll.salary.index');
    }

    
}
