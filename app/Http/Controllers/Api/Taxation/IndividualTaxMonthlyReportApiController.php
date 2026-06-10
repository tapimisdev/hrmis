<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\IndividualTaxMonthlyReportService;
use Illuminate\Http\Request;

class IndividualTaxMonthlyReportApiController extends Controller
{
    public function __construct(
        private readonly IndividualTaxMonthlyReportService $individualTaxMonthlyReportService
    ) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->individualTaxMonthlyReportService->getPagePayload(
                $request->integer('month') ?: null,
                $request->integer('year') ?: null
            )
        );
    }
}
