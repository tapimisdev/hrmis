<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\IndividualTaxDataService;
use Illuminate\Http\Request;

class IndividualTaxApiController extends Controller
{
    public function __construct(
        private readonly IndividualTaxDataService $individualTaxDataService
    ) {}

    public function index(Request $request)
    {
        $payload = $this->individualTaxDataService->getPagePayload(
            $request->string('employee_no')->toString() ?: null,
            $request->integer('year') ?: null,
        );

        return response()->json($payload);
    }
}
