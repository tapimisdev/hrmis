<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreAtroRequest;
use App\Http\Controllers\Admin\Services\ApplicationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AtroController extends Controller
{

    protected $applicationService;

    public function __construct(ApplicationController $applicationService)
    {
        $this->applicationService = $applicationService;

        $this->middleware('permission:emp.overtime_application.view')->only(['index', 'create', 'show']);
        $this->middleware('permission:emp.overtime_application.apply')->only(['store']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {

            $data = $this->applicationService->getRawData('overtime');

            return $this->datatable($data);
        }

        return view('employee.pages.atro.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $myId = Auth::id();
        $data = $this->applicationService->getData('overtime');
        // $approvers = $data['approvers'];
        // $approvers = $approvers->map(function ($collection) use ($myId) {
        //     return $collection->reject(function ($approver) use ($myId) {
        //         return $approver['id'] === $myId;
        //     })->values();
        // });
        $applications = $data['applications'];

        return view('employee.pages.atro.create', compact('applications'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAtroRequest $request)
    {
        $validatedData = $request->validated();

        $userId = Auth::user()->id;

        DB::beginTransaction();

        try {

            // if(empty($validatedData['approvers'])) {
            //     return response([
            //         'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
            //         'status'  => 'error'
            //     ], 500); 
            // }

            $employee_no = DB::table('employee_information')->where('user_id', $userId)->value('employee_no');
            $application_no = generateApplicationNo('overtime_applications', 'PSL');
            // $levels = array_keys($data['approvers']->toArray() ?? []) ?? [];
            // $approvers = $validatedData['approvers'];
            $data = $this->applicationService->getData('overtime');

            $start_time = Carbon::parse($validatedData['start_time']);
            $end_time = Carbon::parse($validatedData['end_time']);

            if ($end_time->lessThan($start_time)) {
                $end_time->addDay();
            }

            $totalHours = $start_time->floatDiffInHours($end_time);
            
            $atroId = DB::table('overtime_applications')
                    ->insertGetId([
                        'application_no' => $application_no,
                        'user_id' => $userId,
                        'employee_no' => $employee_no,
                        'date' => $validatedData['date'],
                        'start_time' => $validatedData['start_time'],
                        'end_time' => $validatedData['end_time'],
                        'total_hours' => $totalHours,
                        'reason' => $validatedData['reason'],
                        'status' => 'pending',
                        'level' => 1,
                        // 'levels' => json_encode($levels)
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

            // foreach ($approvers as $level => $approverList) {
            //     foreach ($approverList as $userId) {
            //         DB::table('overtime_approvals')->insertGetId([
            //             'overtime_applications_id' => $atroId,
            //             'user_id'              => $userId,
            //             'level'                => $level,
            //             'status'               => 'pending',
            //         ]);
            //     }
            // }

            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Overtime application has been submitted',
                'redirect' => route('obs.create')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response([
                'message' => $e->getMessage(),
                'status'  => 'store failed'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->applicationService->getRawData('overtime', $id)[0] ?? [];

        if(!$data) {
            return redirect()->route('overtime.index');
        }

        return response(['data' => $data, 'status' => 'success'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $affected = DB::table('overtime_applications')
                ->where('id', $id)
                ->update(['status' => 'cancelled']);

            DB::commit();
            return response()->json([
                'data' => $affected,
                'message'  => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'destroy failed'
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                return $row->employee_name;
            })
            ->addColumn('date', function ($row) {
                    return \Carbon\Carbon::parse($row->date)->format('M d, Y');
                })
                ->addColumn('status', function ($row) {
                    $status = strtolower($row->status);

                    $badgeClass = match ($status) {
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'dark',
                        'cancelled' => 'danger',
                        default     => 'info',
                    };

                    return '<span class="badge rounded-pill bg-' . $badgeClass . '">' . ucfirst($status) . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '
                        <div class="d-flex">
                            <button data-id="' . $row->id . '" 
                                class="btn btn-primary btn-sm ms-1 show-button" 
                                title="Show">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                    ';

                    // Only show cancel if status is pending or approved
                    if (in_array($row->status, ['pending', 'approved'])) {
                        $buttons .= '
                            <button data-id="' . $row->id . '" 
                                class="btn btn-danger btn-sm ms-1 cancel-button" 
                                title="Cancel">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                        ';
                    }

                    $buttons .= '</div>';

                    return $buttons;
                })
                ->rawColumns(['actions', 'status', 'date'])
                ->make(true);
    }
}
