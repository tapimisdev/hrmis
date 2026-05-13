<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;

class SubsistenceAllowanceController extends Controller
{
    public function index()
    {
        return view('admin.pages.payroll.subsistence-allowance.index');
    }
}
