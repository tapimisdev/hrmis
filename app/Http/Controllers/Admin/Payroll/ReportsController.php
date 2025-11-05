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

        if (request()->ajax()) {
            $query = DB::table('payroll_salary')
                ->where('status', 'approved')
                ->get();

            return $this->datatable($query);
        }

        return view('admin.pages.payroll.reports');
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('employment_type', function ($row) {
               $type = EmploymentTypesEnum::tryFrom((string)$row->employment_type_id)?->name ?? 'N/A';
                return $type;
            })
            ->editColumn('cutoff', function ($row) {
                return str_replace('_', ' ', ucfirst($row->cutoff));
            })
            ->editColumn('period', function ($row) {
                [$month, $year, $period] = explode(' ', $row->period_covered);
                $cutoff = "$period $month $year";

                return $cutoff;
            })
            ->editColumn('payroll_date', function ($row) {
                return date('F d, Y', strtotime($row->payroll_date));
            })
            ->addColumn('actions', function ($row) {
                return
                    '
                        <div class="d-flex gap-2">
                           <a href="' . route('api.payroll.salary.download', $row->payroll_no) . '" class="btn btn-outline-primary btn ms-1 my-1" title="Download">' .
                                '<i class="fa-solid fa-money-bill"></i>' .
                            '</a>
                             <a href="' . route('api.payroll.salary.download', $row->payroll_no) . '" class="btn btn-outline-primary btn ms-1 my-1" title="Download">' .
                                '<i class="fa-solid fa-ghost"></i>' .
                            '</a>
                             <a href="' . route('api.payroll.salary.download', $row->payroll_no) . '" class="btn btn-outline-primary btn ms-1 my-1" title="Download">' .
                                '<i class="fa-solid fa-peso-sign"></i>' .
                            '</a>
                        </div>
                    ';
            })
            ->rawColumns(['actions', 'is_taxable'])
            ->make(true);
    }

}
