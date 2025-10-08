<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Payroll\StoreSalaryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    public function index()
    {
        return view('admin.pages.payroll.salary.index');
    }

    public function create()
    {
        return view('admin.pages.payroll.salary.create');
    }

    public function store(StoreSalaryRequest $request)
    {
        $validatedData = $request->validated();

        dd($validatedData);
    }

    public function destroy($id)
    {

    }
}
