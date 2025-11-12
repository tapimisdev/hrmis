<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Enums\EmploymentTypesEnum;

class ReportsController extends Controller
{
    public function index(Request $request) {

        $positions = DB::table('positions')
            ->orderBy('name', 'asc')
            ->get();
        $employment_types = DB::table('employment_types')->get();
        $tranches = DB::table('tranche')->get();
        $salary_grades = DB::table('tranche_items')
            ->pluck('salary_grade')
            ->unique()
            ->values();

        return view('admin.pages.reports.index', compact('positions', 'employment_types', 'tranches', 'salary_grades'));

    }
}
