<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Services\Taxation\Parts\TaxationBodyService;
use App\Services\Taxation\Parts\TaxationSettingsService;
use App\Services\Taxation\Parts\TaxationCardsService;
use Illuminate\Http\Request;

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

        if($taxation) {
            $taxation->cards = $this->taxationCardsService->getTaxationEmployeesTotalCards($taxation->id ?? 0) ?? [];
            $taxation->body = $this->taxationBodyService->getEmployees($taxation->id) ?? [];
        }

        return response()->json($taxation);
    }
}