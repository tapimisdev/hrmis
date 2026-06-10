<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\IndividualTaxMonthlyReportService;
use Illuminate\Http\Request;

class IndividualTaxMonthlyReportController extends Controller
{
    public function __construct(
        private readonly IndividualTaxMonthlyReportService $individualTaxMonthlyReportService
    ) {}

    public function index(Request $request)
    {
        $payload = $this->individualTaxMonthlyReportService->getPagePayload(
            $request->integer('month') ?: null,
            $request->integer('year') ?: null
        );

        return view('admin.pages.taxation.individual-tax-report.index', $payload);
    }
}
