<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
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

    public function getBatchProgress($batch_id)
    {
        $batch = Bus::findBatch($batch_id);

        if (! $batch) {
            return response()->json([
                'error' => 'Batch not found'
            ], 404);
        }

        $status = 'processing';

        if ($batch->cancelled()) {
            $status = 'cancelled';
        } elseif ($batch->failedJobs > 0) {
            $status = 'failed';
        } elseif ($batch->finished()) {
            $status = 'finished';
        }

        return response()->json([
            'progress' => round($batch->progress()),
            'processedJobs' => $batch->processedJobs(),
            'totalJobs' => $batch->totalJobs,
            'pendingJobs' => $batch->pendingJobs,
            'failedJobs' => $batch->failedJobs,
            'status' => $status,
            'cancelled_at' => $batch->cancelledAt,
        ]);
    }

    public function cancelBatch($id)
    {
        $batch = Bus::findBatch($id);

        if (!$batch) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Batch not found.',
            ], 404);
        }

        $batch->cancel();

        return response()->json([
            'status' => 'cancelled',
            'batch_id' => $batch->id,
            'name' => $batch->name,
            'progress' => $batch->progress(),
        ]);
    }

    public function getApprovers()
    {
        $approver_id = DB::table('application_approver')
                        ->where('type', 'payroll')
                        ->value('id');

        $user_approvers = DB::table('application_approver_users as au')
                            ->leftJoin('employee_information as ei', 'au.user_id', '=', 'ei.user_id')
                            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
                            ->where('au.application_approver_id', $approver_id)
                            ->select(
                                'ei.employee_no',
                                'au.level',
                                DB::raw('UPPER(ep.firstname) as firstname'),
                                DB::raw('UPPER(ep.lastname) as lastname'),
                                'ep.middlename',
                                'ei.user_id'
                            )
                            ->get();

        return response()->json($user_approvers);
    }
    
}
