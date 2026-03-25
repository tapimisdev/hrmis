<?php

namespace App\Http\Controllers\Admin\Payroll\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\HazardImportService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use RuntimeException;

class HazardRegistryController extends Controller
{
    protected HazardImportService $hazardImportService;

    public function __construct(HazardImportService $hazardImportService)
    {
        $this->hazardImportService = $hazardImportService;
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

        $active = 'hazard payroll';

        return view('admin.pages.payroll.import.hazard', compact(
            'active',
            'options'
        ));
    }

    public function store(Request $request)
    {
        if ($request->boolean('isImport') && $request->filled('data')) {
            return $this->hazardImportService->importRegular($request->input('data', []));
        }

        $request->validate($this->rules());

        $file = $request->file('file');
        $path = $file->getRealPath();

        try {
            $parsedData = $this->hazardImportService->cleanRegular($path);
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

        $periodCovered = Carbon::createFromFormat('Y-m', $request->month)->format('F Y');

        return response()->json([
            'label' => $request->label,
            'period_covered' => $periodCovered,
            'type' => 'Hazard Payroll',
            'coverage' => 'Regular',
            'month' => $request->month,
            'data' => $parsedData['rows'],
            'preview_headers' => $parsedData['preview_headers'],
            'field_order' => $parsedData['field_order'],
            'errors' => $parsedData['errors'] ?? [],
        ]);
    }

    private function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xls,xlsx,csv,txt',
            'label' => 'required|string',
            'month' => ['required', 'date_format:Y-m'],
        ];
    }
}
