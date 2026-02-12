<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImportRegistryController extends Controller
{
    public function index() {
        return view('admin.pages.payroll.import.registry');
    }
}
