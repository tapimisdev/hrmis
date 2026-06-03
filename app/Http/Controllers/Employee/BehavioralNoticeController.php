<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class BehavioralNoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:emp.behavioral_notices.view');
    }

    public function index()
    {
        return view('employee.pages.behavioral-notices.index');
    }
}
