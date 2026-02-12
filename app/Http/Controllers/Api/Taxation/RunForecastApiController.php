<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\RunForecastRequest;
use App\Jobs\Taxation\ForeCastEmployeeJob;
use App\Services\Taxation\RunForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $taxation_id = $this->run_forecast_service->createTaxation($validated_data);

            // dd($validated_data, $taxation_id);

            foreach($employee_nos as $emp_no) {
                ForeCastEmployeeJob::dispatch($taxation_id, $emp_no, $validated_data);
            }

            DB::commit();

            return response()->json([
                'message' => 'success'
            ], 200);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {

            // fallback (unexpected errors)
            return response()->json([
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }


    }
}
