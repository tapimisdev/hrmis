<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\SaveIndividualTaxRequest;
use App\Services\Taxation\IndividualTaxDataService;
use App\Services\Taxation\SaveIndividualTaxService;
use Illuminate\Http\Request;

class IndividualTaxController extends Controller
{
    public function __construct(
        private readonly IndividualTaxDataService $individualTaxDataService,
        private readonly SaveIndividualTaxService $saveIndividualTaxService,
    ) {}

    public function index(Request $request)
    {
        $payload = $this->individualTaxDataService->getPagePayload(
            $request->string('employee_no')->toString() ?: null,
            $request->integer('year') ?: null,
        );

        return view('admin.pages.taxation.individual-tax.index', $payload);
    }

    public function save(SaveIndividualTaxRequest $request)
    {
        return response()->json(
            $this->saveIndividualTaxService->handle($request->validated()),
            201
        );
    }
}
