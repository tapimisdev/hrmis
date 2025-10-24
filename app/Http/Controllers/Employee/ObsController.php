<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreObsRequest;
use App\Http\Controllers\Admin\Services\ApplicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ObsController extends Controller
{

    protected $applicationService;

    public function __construct(ApplicationController $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {

            $data = $this->applicationService->getRawData('obs');
            return $this->datatable($data);
        }

        return view('employee.pages.obs.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $myId = Auth::id();
        $data = $this->applicationService->getData('leave');
        $approvers = $data['approvers'];
        $approvers = $approvers->map(function ($collection) use ($myId) {
            return $collection->reject(function ($approver) use ($myId) {
                return $approver['id'] === $myId;
            })->values();
        });
        
        $applications = $data['applications'];

        return view('employee.pages.obs.create', compact('approvers', 'applications'));
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreObsRequest $request)
    {
        $validatedData = $request->validated();

        $user = Auth::user()->load('employeeInformation');
        $employee_no = $user->toArray()['employee_information']['employee_no'];

        DB::beginTransaction();

        try {

            if(empty($validatedData['approvers'])) {
                return response([
                    'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
                    'status'  => 'error'
                ], 500); 
            }

            $application_no = generateApplicationNo('obs_applications', 'PSL');
            
            $approvers = $validatedData['approvers'];
            $data = $this->applicationService->getData('leave');
            $levels = array_keys($data['approvers']->toArray() ?? []) ?? [];

            // Insert obs record
            $obsId = DB::table('obs_applications')->insertGetId([
                'application_no'     => $application_no,
                'user_id'            => Auth::user()->id,
                'employee_no'        => $employee_no,
                'date_from'          => $validatedData['date_from'],
                'date_to'            => $validatedData['date_to'],
                'time_out'           => $validatedData['time_out'] ?? null,
                'time_in'            => $validatedData['time_in'] ?? null,
                'destination'        => $validatedData['destination'],
                'purpose'            => $validatedData['purpose'],
                'mode_of_transport'  => $validatedData['mode_of_transport'] ?? null,
                'estimated_expense'  => $validatedData['estimated_expense'] ?? 0,
                'charge_to'          => $validatedData['charge_to'] ?? null,
                'remarks'            => $validatedData['remarks'] ?? null,
                'status'             => 'pending',
                'level'              => 1,
                'levels'             => json_encode($levels),
                'created_by'         => Auth::user()->id,
                'updated_by'         => Auth::user()->id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('obs_attachments', 'public');
                    DB::table('obs_attachments')->insert([
                        'obs_applications_id'     => $obsId,
                        'file_path'  => $path,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($approvers as $level => $approverList) {
                foreach ($approverList as $userId) {
                    DB::table('obs_approvals')->insertGetId([
                        'obs_applications_id' => $obsId,
                        'user_id'              => $userId,
                        'level'                => $level,
                        'status'               => 'pending',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pass slip application has been submitted',
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
    public function show(int $id)
    {
        
        $data = $this->applicationService->getRawData('obs', $id)[0] ?? [];

        if(!$data) {
            return redirect()->route('obs.index');
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
            $affected = DB::table('obs_applications')
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
            ->addColumn('date_range', function ($row) {
                    if ($row->date_from == $row->date_to) {
                        // Single day leave
                        return '<span class="badge rounded-pill bg-primary">'
                                . \Carbon\Carbon::parse($row->date_from)->format('M d, Y') .
                            '</span>';
                    } else {
                        // Multi-day leave
                        return '<span class="badge rounded-pill bg-primary me-1">'
                                . \Carbon\Carbon::parse($row->date_from)->format('M d, Y') .
                            '</span>' . 'to ' .
                            '<span class="badge rounded-pill bg-success">'
                                . \Carbon\Carbon::parse($row->date_to)->format('M d, Y') .
                            '</span>';
                    }
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
                ->rawColumns(['actions', 'status', 'date_range'])
                ->make(true);
    }
}
