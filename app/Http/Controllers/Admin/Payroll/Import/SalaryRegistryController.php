<?php

namespace App\Http\Controllers\Admin\Payroll\Import;

use App\Http\Controllers\Controller;
use App\Services\Import\SalaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalaryRegistryController extends Controller
{
    protected SalaryService $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * Show import page.
     */
    public function index()
    {
        $employment_types = DB::table('employment_types')->get();

        $options = [
            'salary payroll',
            'hazard payroll',
            'sla payroll',
            'pera & rata payroll',
            'longevity payroll'
        ];

        $active = 'salary payroll';

        return view('admin.pages.payroll.import.salary', compact(
            'active',
            'employment_types',
            'options'
        ));
    }

    /**
     * Handle parsing and import.
     */
    public function store(Request $request)
    {
        $employment_type = (int) $request->employment_type;

        // Second submit: Import action
        if ($request->boolean('isImport') && $request->filled('data')) {
            return $this->importPayroll($employment_type, $request->data);
        }

        // Validate form input
        $request->validate($this->rules());

        // First submit: Parse action
        $file = $request->file('file');
        $path = $file->getRealPath();

        $parsedData = $this->parsePayroll($employment_type, $path);

        $period_covered = Carbon::parse($request->date)->format('F Y') . ' ' .
            ($request->cut_off_period === 'first_cutoff'
                ? '1-15'
                : '16-' . Carbon::parse($request->date)->endOfMonth()->format('d')
            );

        $parsedData = [
            'label' => $request->label,
            'cutoff' => $request->cut_off_period,
            'period_covered' => $period_covered,
            'type' => 'Salary Payroll',
            'employment_type' => $employment_type,
            'data' => $parsedData
        ];

        return response()->json($parsedData);
    }

    /**
     * Validation rules.
     */
    private function rules(): array
    {
        return [
            'employment_type' => 'required|exists:employment_types,id',
            'file' => 'required|file|mimes:xls,xlsx',
            'label' => 'required|string',
            'date' => 'required|date',
            'cut_off_period' => 'required|in:first_cutoff,second_cutoff',
        ];
    }

    /**
     * Parse payroll file based on employment type and payroll type.
     */
    private function parsePayroll(int $employment_type, string $path): array
    {
        return $employment_type === 1
            ? $this->salaryService->cleanRegular($path)
            : $this->salaryService->cleanCOS($path);
    }

    /**
     * Import payroll data after parsing.
     */
    private function importPayroll(int $employment_type, array $data)
    {
        $employment_type === 1
            ? $this->salaryService->importRegular($data)
            : $this->salaryService->importCOS($data);

        return response()->json([
            'status' => 'success',
            'message' => 'imported successfully.'
        ]);
    }
}