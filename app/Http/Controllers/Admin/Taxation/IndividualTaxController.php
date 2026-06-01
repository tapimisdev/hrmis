<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\IndividualTaxDataService;
use Illuminate\Http\Request;

class IndividualTaxController extends Controller
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

        return view('admin.pages.taxation.individual-tax.index', $payload);
    }
}
