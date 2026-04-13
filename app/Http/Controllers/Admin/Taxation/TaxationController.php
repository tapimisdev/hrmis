<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\ApplyForecastToPayrollRequest;
use App\Services\Taxation\ApplyForecastToPayrollService;
use App\Services\Taxation\Parts\TaxationBodyService;
use App\Services\Taxation\Parts\TaxationSettingsService;
use App\Services\Taxation\Parts\TaxationCardsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaxationController extends Controller
{
    public function __construct(
        private readonly TaxationSettingsService $taxationSettingsService,
        private readonly TaxationCardsService $taxationCardsService,
        private readonly TaxationBodyService $taxationBodyService,
        private readonly ApplyForecastToPayrollService $applyForecastToPayrollService,
    ) {}

    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));
        $type = $this->resolveTaxationType($request);

        if(!$this->allowYears($year)) {
            abort(404);
        }

        if (!$request->wantsJson()) {
            return view('admin.pages.taxation.taxation.index');
        }

        $taxation = $this->taxationSettingsService->getActiveTaxationWithSettings((int) $year);

        if ($taxation) {

            // if($taxation->status === 'processing') {
            //     return response()->json([]);
            // }

            $taxation->cards = $this->taxationCardsService->getTaxationEmployeesTotalCards($taxation->id ?? 0) ?? [];
            $taxation->body = $this->taxationBodyService->getEmployees($taxation->id, $type) ?? [];
        }

        return response()->json($taxation);
    }

    public function status(Request $request)
    {
        $batchId = $request->query('batch_id');

        if (!$batchId) {
            return response()->json([
                'message' => 'Batch ID is required.'
            ], 400);
        }

        $jobBatch = DB::table('job_batches')
            ->where('id', $batchId)
            ->first();

        if (!$jobBatch) {
            return response()->json([
                'message' => 'Batch not found.',
                'processed_percentage' => 0,
                'pending_percentage'   => 0,
                'is_finished'          => false,
            ], 404);
        }

        if ($jobBatch->total_jobs == 0) {
            return response()->json([
                'processed_percentage' => 0,
                'pending_percentage'   => 0,
                'is_finished'          => false,
            ]);
        }

        $processed = $jobBatch->total_jobs - $jobBatch->pending_jobs;

        $processedPercentage = (int) round(
            ($processed / $jobBatch->total_jobs) * 100
        );

        $pendingPercentage = 100 - $processedPercentage;

        $isFinished = $processedPercentage >= 100;

        return response()->json([
            'total_jobs'           => $jobBatch->total_jobs,
            'pending_jobs'         => $jobBatch->pending_jobs,
            'failed_jobs'          => $jobBatch->failed_jobs,
            'processed_percentage' => $processedPercentage,
            'pending_percentage'   => $pendingPercentage,
            'is_finished'          => $isFinished,
        ]);
    }

    public function destroy($taxation_id)
    {
        $updated = DB::table('taxations')
            ->where('id', $taxation_id)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'updated_at' => now()
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Record not found or already deleted.'
            ], 404);
        }

        return response()->json([
            'message' => 'Record deleted successfully.'
        ]);
    }

    public function applyToPayroll(ApplyForecastToPayrollRequest $request)
    {
        try {
            $result = $this->applyForecastToPayrollService->handle(
                (int) $request->validated('taxation_id')
            );

            return response()->json([
                'message' => "Payroll tax tables updated for {$result['employee_count']} employee(s) using taxation year {$result['year']}.",
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Failed to apply forecast to Payroll.',
            ], 500);
        }
    }

    private function allowYears($year) : bool
    {
        $allowed= [];
        $current_year = Carbon::now()->year + 2;


        for($i = 0; $i <= 9; $i++) {
            $allowed[] = $current_year - $i;
        }

        if(in_array($year, $allowed)) {
            return true;
        }

        return false;
    }

    private function resolveTaxationType(Request $request): string
    {
        $type = $request->query('type', $request->query('quarter', 'forecast'));
        $allowedTypes = ['forecast', 'q2', 'q3', 'q4', 'nov', 'final'];

        return in_array($type, $allowedTypes, true) ? $type : 'forecast';
    }
}
