<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\RunForecastRequest;
use App\Jobs\Taxation\ForeCastEmployeeJob;
use App\Services\Taxation\RunForecastService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class RunForecastApiController extends Controller
{

    protected $run_forecast_service;

    public function __construct(RunForecastService $run_forecast_service)
    {
        $this->run_forecast_service = $run_forecast_service;
    }

    public function run(RunForecastRequest $request)
    {
        $validated_data = $request->validated();

        DB::beginTransaction();
        try {
            $employee_nos = $this->run_forecast_service->getAllEmployees();
            $taxation_id  = $this->run_forecast_service->createTaxation($validated_data);

            // Build jobs
            $jobs = collect($employee_nos)
                ->map(fn($emp_no) => new ForeCastEmployeeJob($taxation_id, $emp_no, $validated_data))
                ->values()
                ->all();

            // Dispatch as a batch (still within transaction so we can store batch id safely)
            $batch = Bus::batch($jobs)
                ->name("Forecast Taxation #{$taxation_id}")
                ->then(function (Batch $batch) use ($taxation_id) {
                    // all jobs completed successfully
                    DB::table('taxations')->where('id', $taxation_id)->update(['status' => 'completed']);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($taxation_id) {
                    DB::table('taxations')->where('id', $taxation_id)->update(['status' => 'failed']);
                })
                ->dispatch();

            // save batch id in taxation row
            DB::table('taxations')->where('id', $taxation_id)->update(['batch_id' => $batch->id]);

            DB::commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage(),], $e->getStatusCode());
        } catch (\Exception $e) {
            // fallback (unexpected errors)
            return response()->json([
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
