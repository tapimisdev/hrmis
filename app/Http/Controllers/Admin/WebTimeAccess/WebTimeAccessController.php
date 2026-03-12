<?php

namespace App\Http\Controllers\Admin\WebTimeAccess;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebTimeAccessRequest;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

use const Adminer\DB;

class WebTimeAccessController extends Controller
{
    protected $employeeService;

    public function __construct()
    {
        $this->employeeService = app(EmployeeService::class);

        $this->middleware('permission:hr.webtime.view')->only(['index', 'show']);
        $this->middleware('permission:hr.webtime.create')->only('store');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {

            $status = $request->get('account_status');
            $division_id = $request->get('division');
            $unit_id = $request->get('unit');
            $employment_type = $request->get('employment_type');

            $query = $this->employeeService->getEmployees($status, $division_id, $unit_id, $employment_type);
            
            return $this->datatable($query);
        }

        $divisions = DB::table('divisions')->get();
        $division_id = $request->division;
        $unit_id = $request->unit;

        return view('admin.pages.web-time-access.index', compact(
            'divisions', 'division_id', 'unit_id'
        ));

    }

    public function store(StoreWebTimeAccessRequest $request) 
    {
        $validated = $request->validated();

        // Conditional validation
        if ($validated['type'] === 'days_of_week' && empty($validated['days_of_week'])) {
            return response()->json([
                'errors' => [
                    'days_of_week' => ['Please select at least one day.']
                ]
            ], 422);
        }

        if ($validated['type'] === 'specific_dates' && empty($validated['specific_dates'])) {
            return response()->json([
                'errors' => [
                    'specific_dates' => ['Please add at least one date.']
                ]
            ], 422);
        }

        // Normalize
        $type = $validated['type'];
        $isRequiredAccomplishment = $validated['is_required_accomplishment'] == 'yes' ? true : false;

        $always = $type === 'always';

        $daysOfWeek = $type === 'days_of_week'
            ? json_encode(array_values(array_unique($validated['days_of_week'])))
            : null;

        $specificDates = $type === 'specific_dates'
            ? json_encode(array_values(array_unique($validated['specific_dates'])))
            : null;

        DB::beginTransaction();


        try {
            foreach ($validated['employee_nos'] as $employeeNo) {

                $data = [
                    'always'           => $always,
                    'days_of_week'     => $daysOfWeek,
                    'specific_dates'   => $specificDates,
                    'isRequiredAccomplishment' => $isRequiredAccomplishment,
                    'effectivity_date' => now(),
                    'updated_at'       => now(),
                ];

                DB::table('web_time_access')
                    ->insert(array_merge($data, [
                        'employee_no' => $employeeNo,
                        'created_at'  => now(),
                    ]));
            }

            DB::commit();

            return response()->json([
                'message' => 'Schedule saved successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to save schedule.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show($employeeNo)
    {
        $rows = DB::table('web_time_access')
            ->where('employee_no', $employeeNo)
            ->orderByDesc('effectivity_date')
            ->orderByDesc('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'message' => 'No schedule history found for this employee.',
                'data' => [],
            ], 404);
        }

        $data = $rows->map(function ($row) {
            // determine type
            $type = 'always';
            if (!empty($row->days_of_week)) $type = 'days_of_week';
            if (!empty($row->specific_dates)) $type = 'specific_dates';
            if (!empty($row->always)) $type = 'always';

            return [
                'id'               => $row->id,
                'employee_no'      => $row->employee_no,
                'type'             => $type,

                'days_of_week' => $row->days_of_week
                    ? json_decode($row->days_of_week, true)
                    : [],

                'specific_dates' => $row->specific_dates
                    ? collect(json_decode($row->specific_dates, true))
                        ->map(fn ($date) => Carbon::parse($date)->format('F j, Y'))
                        ->values()
                        ->all()
                    : [],

                'effectivity_date' => $row->effectivity_date
                    ? Carbon::parse($row->effectivity_date)->format('F j, Y')
                    : null,

                'isRequiredAccomplishment' => $row->isRequiredAccomplishment,
                'created_at'       => $row->created_at,
            ];
        });

        return response()->json([
            'message' => 'Schedule history fetched successfully.',
            'data' => $data, 
        ]);
    }

    public function destroy($id)
    {
        try {

            $deleted = DB::table('web_time_access')
                ->where('id', $id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'message' => 'Schedule not found.',
                ], 404);
            }

            return response()->json([
                'message' => 'Schedule deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Failed to delete schedule.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            
            ->editColumn('profile', function ($row) {
                $profile = $row->profile ?? null;

                if ($profile) {
                    $profile = Storage::url('public/users/' . $row->employee_no . '/profile-image/' . $row->profile);
                } else {
                    $profile = 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
                        . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                }

                return '<div style="width: 50px; height: 50px; border:1px solid #ccc; border-radius:8px; 
                                    display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                            <img src="' . $profile . '" 
                                alt="Avatar of ' . e(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')) . '" 
                                style="width:100%; height:100%; object-fit:cover;">
                        </div>';
            })
            ->editColumn('employee_no', function ($row) {
                return $row->employee_no;
            })
            ->editColumn('name', function ($row) {
                return empty($row->firstname) && empty($row->lastname)
                    ? '<i class="text-muted">No Name Yet</i>'
                    : $row->firstname . ' ' . $row->lastname;
            })
            ->editColumn('date_hired', function ($row) {
                return $row->date_hired_organization ? Carbon::parse($row->date_hired_organization)->format('F d, Y')
                    : '';
            })
            
            ->rawColumns(['actions', 'employee_no', 'profile', 'name', 'date_hired']) // make sure checkbox is rendered as HTML
            ->make(true);
    }

}
