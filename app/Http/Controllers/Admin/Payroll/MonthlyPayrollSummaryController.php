<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Services\Payroll\MonthlyPayrollSummaryService;
use Illuminate\Http\Request;

class MonthlyPayrollSummaryController extends Controller
{
    public function __construct(
        private readonly MonthlyPayrollSummaryService $monthlyPayrollSummaryService
    ) {
        $this->middleware('permission:payroll.monthly-summary.view')->only('index');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
        ]);

        $filters = $this->monthlyPayrollSummaryService->normalizeFilters($validated);
        $summaries = $this->monthlyPayrollSummaryService->paginate($filters);
        $years = collect($this->monthlyPayrollSummaryService->availableYears())
            ->push((int) $filters['year'])
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
        $months = collect(range(1, 12))
            ->mapWithKeys(fn ($month) => [$month => date('F', mktime(0, 0, 0, $month, 1))])
            ->all();

        return view('admin.pages.payroll.monthly-summary.index', compact(
            'filters',
            'summaries',
            'years',
            'months'
        ));
    }
}
