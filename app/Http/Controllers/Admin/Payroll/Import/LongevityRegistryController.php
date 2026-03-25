<?php

namespace App\Http\Controllers\Admin\Payroll\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\LongevityImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RuntimeException;

class LongevityRegistryController extends Controller
{
    protected LongevityImportService $longevityImportService;

    public function __construct(LongevityImportService $longevityImportService)
    {
        $this->longevityImportService = $longevityImportService;
    }

    public function index()
    {
        $options = [
            'salary payroll' => route('registry.salary.index'),
            'hazard payroll' => route('registry.hazard.index'),
            'sla payroll' => route('registry.sla.index'),
            'pera & rata payroll' => route('registry.pera-rata.index'),
            'longevity payroll' => route('registry.longevity.index'),
        ];

        $active = 'longevity payroll';

        return view('admin.pages.payroll.import.longevity', compact(
            'active',
            'options'
        ));
    }

    public function store(Request $request)
    {
        if ($request->boolean('isImport') && $request->filled('data')) {
            return $this->longevityImportService->importRegular($request->input('data', []));
        }

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv,txt',
            'label' => 'required|string',
            'month' => ['required', 'date_format:Y-m'],
        ]);

        try {
            $parsedData = $this->longevityImportService->cleanRegular(
                $request->file('file')->getRealPath()
            );
        } catch (RuntimeException $exception) {
            if (str_starts_with($exception->getMessage(), 'Missing required header(s):')) {
                $headers = array_map('trim', explode(',', str_replace('Missing required header(s):', '', $exception->getMessage())));

                return response()->json([
                    'error_type' => 'missing_headers',
                    'title' => 'Template headers do not match',
                    'message' => 'The uploaded file could not be parsed because one or more required column headers are missing or renamed. Review the file and make sure these headers are present exactly as expected.',
                    'missing_headers' => array_values(array_filter($headers)),
                ], 422);
            }

            throw $exception;
        }

        return response()->json([
            'label' => $request->label,
            'period_covered' => Carbon::createFromFormat('Y-m', $request->month)->format('F Y'),
            'type' => 'Longevity Payroll',
            'coverage' => 'Regular',
            'month' => $request->month,
            'data' => $parsedData['rows'],
            'preview_headers' => $parsedData['preview_headers'],
            'field_order' => $parsedData['field_order'],
            'errors' => $parsedData['errors'] ?? [],
        ]);
    }
}
