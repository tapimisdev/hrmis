<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportsApiController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:hr.report.search')->only('index');
    }

    public function index(Request $request)
    {
        $payload = $request->all();

        // --- Total count of employee_personal rows ---
        $totalEmployees = DB::table('employee_personal')->count();

        // --- Main query ---
        $query = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoin('employee_organization as eo', function ($join) {
                $join->on('ei.employee_no', '=', 'eo.employee_no')
                    ->whereRaw('eo.id = (SELECT MAX(id) FROM employee_organization WHERE employee_no = ei.employee_no)');
            })
            ->leftJoin('positions as p', 'eo.position_id', '=', 'p.id')
            ->leftJoin('employee_salary as es', function ($join) {
                $join->on('ei.employee_no', '=', 'es.employee_no')
                    ->whereRaw('es.id = (SELECT MAX(id) FROM employee_salary WHERE employee_no = ei.employee_no)');
            })
            ->leftJoin('tranche as t', 'es.tranche_id', '=', 't.id')
            ->select(
                'ei.employee_no',
                'ep.firstname',
                'ep.lastname',
                'ep.sex',
                'ep.civil_status',
                'p.name as position_name',
                't.description as tranche_name',
                'es.salary_grade',
                'ei.account_status',
                DB::raw("DATE_FORMAT(ei.date_hired_organization, '%M %d, %Y') AS date_hired_organization"),
                'ei.date_resigned'
            )
            ->when(!empty($payload['account_status']), fn($q) => 
                $q->where('ei.account_status', $payload['account_status'])
            )
            ->when(!empty($payload['date_hired_organization']), fn($q) => 
                $q->where('ei.date_hired_organization', $payload['date_hired_organization'])
            )
            ->when(!empty($payload['employment_type']), fn($q) => 
                $q->where('eo.employment_type_id', $payload['employment_type'])
            )
            ->when(!empty($payload['position']), fn($q) => 
                $q->where('eo.position_id', $payload['position'])
            )
            ->when(!empty($payload['tranche_id']), fn($q) => 
                $q->where('es.tranche_id', $payload['tranche_id'])
            )
            ->when(!empty($payload['salary_grade']), fn($q) => 
                $q->where('es.salary_grade', $payload['salary_grade'])
            )
            ->when(!empty($payload['sex']), fn($q) => 
                $q->where('ep.sex', $payload['sex'])
            )
            ->when(!empty($payload['civil_status']), fn($q) => 
                $q->where('ep.civil_status', $payload['civil_status'])
            );

        $records = $query->get();

        // --- Prepare data for session ---
        $exportData = [];
        if ($records->isNotEmpty()) {
            $first = (array) $records->first();
            $headers = array_map(fn($key) => ucwords(str_replace('_', ' ', $key)), array_keys($first));
            $exportData[] = $headers;

            foreach ($records as $record) {
                $exportData[] = array_values((array) $record);
            }
        }

        // --- Save filtered data to cache ---
        Cache::put('export_employee_data', $exportData, now()->addMinutes(1)); 

        // --- Applied filters ---
        $appliedFilters = array_filter([
            'account_status' => $payload['account_status'] ?? null,
            'date_hired_organization' => $payload['date_hired_organization'] ?? null,
            'employment_type' => $payload['employment_type'] ?? null,
            'position' => $payload['position'] ?? null,
            'tranche_id' => $payload['tranche_id'] ?? null,
            'salary_grade' => $payload['salary_grade'] ?? null,
            'sex' => $payload['sex'] ?? null,
            'civil_status' => $payload['civil_status'] ?? null
        ]);

        return response()->json([
            'total_count' => $totalEmployees,
            'filtered_count' => $records->count(),
            'applied_filters' => $appliedFilters,
            'data' => $exportData
        ]);
    }

    public function download(Request $request)
    {
        $fileType = $request->fileType;

        $data = Cache::get('export_employee_data');

        if (!$data) {
            return response()->json(['message' => 'Unable to download data'], 404);
        }

        if ($fileType == 'csv') {
            $response = new StreamedResponse(function() use ($data) {
                $handle = fopen('php://output', 'w');
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            });

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="report.csv"');

            return $response;
        }

        if ($fileType == 'excel') {
            $export = new class($data) implements FromArray, WithHeadings {
                private $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return array_slice($this->data, 1); 
                }

                public function headings(): array
                {
                    return $this->data[0] ?? [];
                }
            };

            return Excel::download($export, 'report.xlsx');
        }

        return response()->json(['message' => 'Unsupported file type.'], 400);
    }
}
