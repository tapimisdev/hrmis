<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\Parts\TaxationBodyService;
use App\Services\Taxation\Parts\TaxationSettingsService;
use App\Services\Taxation\Parts\TaxationCardsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxationController extends Controller
{
    public function __construct(
        private readonly TaxationSettingsService $taxationSettingsService,
        private readonly TaxationCardsService $taxationCardsService,
        private readonly TaxationBodyService $taxationBodyService,
    ) {}

    public function index(Request $request)
    {
        $year = $request->query('year', date('Y'));

        if (!$request->wantsJson()) {
            return view('admin.pages.taxation.taxation.index');
        }

        $taxation = $this->taxationSettingsService->getActiveTaxationWithSettings((int) $year);

        // dd($taxation);


        if ($taxation) {

            // if($taxation->status === 'processing') {
            //     return response()->json([]);
            // }

            $taxation->cards = $this->taxationCardsService->getTaxationEmployeesTotalCards($taxation->id ?? 0) ?? [];
            $taxation->body = $this->taxationBodyService->getEmployees($taxation->id) ?? [];
        }

        // dd($taxation);

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

    public function breakdowns($taxation_employee_id)
    {

        $computations = DB::table('taxation_employee_computations')
            ->where('taxation_employee_id', $taxation_employee_id)
            ->get()
            ->keyBy('type') // This sets the key to the value of the 'type' column
            ->map(function ($item) {
                $item->raw_computation = json_decode($item->raw_computation, true);
                return $item;
            });

        return response()->json($computations);
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
}
