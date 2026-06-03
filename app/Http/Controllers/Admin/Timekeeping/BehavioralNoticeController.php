<?php

namespace App\Http\Controllers\Admin\Timekeeping;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BehavioralNoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.timekeeping.view');
    }

    public function index()
    {
        return view('admin.pages.timekeeping.behavioral-notices.index');
    }

    public function employees()
    {
        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->whereNotNull('ei.user_id')
            ->where('ei.isDeleted', false)
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->get([
                'ei.user_id',
                'ei.employee_no',
                'ep.firstname',
                'ep.lastname',
            ])
            ->map(fn (object $employee) => [
                'user_id' => $employee->user_id,
                'employee_no' => $employee->employee_no,
                'name' => trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: $employee->employee_no,
            ]);

        return response()->json([
            'employees' => $employees,
        ]);
    }

    public function data(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        if ($request->filled('id')) {
            $linkedNotice = DB::table('employee_violations')
                ->where('id', $request->integer('id'))
                ->first(['month', 'year']);

            if ($linkedNotice) {
                $month = (int) $linkedNotice->month;
                $year = (int) $linkedNotice->year;
            }
        }

        $period = Carbon::create($year, $month, 1);

        $baseQuery = DB::table('employee_violations as ev')
            ->leftJoin('employee_information as ei', 'ev.user_id', '=', 'ei.user_id')
            ->leftJoin('employee_personal as ep', 'ev.employee_no', '=', 'ep.employee_no')
            ->when($request->filled('employee_no'), fn ($query) => $query->where('ev.employee_no', $request->employee_no))
            ->where('ev.month', $period->month)
            ->where('ev.year', $period->year);

        $violationTypes = (clone $baseQuery)
            ->whereNotNull('ev.violation_type')
            ->distinct()
            ->orderBy('ev.violation_type')
            ->pluck('ev.violation_type')
            ->values();

        $notices = (clone $baseQuery)
            ->when($request->filled('violation_type'), fn ($query) => $query->where('ev.violation_type', $request->violation_type))
            ->orderByDesc('ev.generated_at')
            ->orderBy('ep.lastname')
            ->orderBy('ep.firstname')
            ->orderBy('ev.violation_type')
            ->get([
                'ev.*',
                'ep.firstname',
                'ep.lastname',
                'ei.employee_no as current_employee_no',
            ])
            ->map(function (object $notice) {
                $notice->details = is_string($notice->details)
                    ? json_decode($notice->details, true)
                    : $notice->details;
                $notice->employee_name = trim(($notice->firstname ?? '') . ' ' . ($notice->lastname ?? '')) ?: ($notice->employee_no ?? 'Unknown employee');
                $notice->employee_no = $notice->employee_no ?: $notice->current_employee_no;

                unset($notice->firstname, $notice->lastname, $notice->current_employee_no);

                return $notice;
            });

        return response()->json([
            'behavioral_notices' => $notices,
            'violation_types' => $violationTypes,
            'period' => $period->format('F Y'),
            'filters' => [
                'month' => $period->month,
                'year' => $period->year,
            ],
        ]);
    }
}
