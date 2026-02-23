<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreObsRequest;
use App\Http\Controllers\Admin\Services\ApplicationController;
use App\Services\EventService;
use App\Events\NotificationEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ObsController extends Controller
{

    protected $applicationService;
    protected $EventService;

    public function __construct(ApplicationController $applicationService, EventService $EventService)
    {

        $this->middleware('permission:emp.pass_slip_application.view')->only(['index', 'create', 'show']);
        $this->middleware('permission:emp.pass_slip_application.apply')->only(['create', 'store']);
    
        $this->applicationService = $applicationService;
        $this->EventService = $EventService;
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
        $data = $this->applicationService->getData(['leave', 'offset', 'obs', 'special_order']);
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
        $isDirectlyApproved = $validatedData['isDirectlyApproved'] ?? false;

        if(!empty($validatedData['user_id'])) {
            $user = User::with('employeeInformation')->findOrFail($validatedData['user_id']);
            $employee_no = $user->employeeInformation->employee_no;
            $user_id = $user->id;
        } else { 
            $user = Auth::user()->load('employeeInformation');
            $employee_no = $user->toArray()['employee_information']['employee_no'];
            $user_id = Auth::user()->id;
        }

        DB::beginTransaction();

        try {

            // if(empty($validatedData['approvers'])) {
            //     return response([
            //         'message' => 'Unable to submit application, no approvers assigned. Please contact administrator',
            //         'status'  => 'error'
            //     ], 500); 
            // }

            $datesInput = $validatedData['selectedDates'];

            if (is_string($datesInput)) {
                $dates = json_decode($datesInput, true);
            } elseif (is_array($datesInput)) {
                $dates = $datesInput;
            } else {
                $dates = [];
            }

            // $data = $this->applicationService->getData(['obs']);
            // $levels = array_keys($data['approvers']->toArray() ?? []) ?? [];
            // $approvers = $validatedData['approvers'];

            $applications = $data['applications'] ?? [];

            $application_no = generateApplicationNo('obs_applications', 'PSL');

            $name = 'Pass Slip';

            $applicationID = DB::table('obs_applications')->insertGetId([
                'application_no'     => $application_no,
                'name'               => $name,
                'user_id'            => $user_id,
                'employee_no'        => $employee_no,
                'reason'             => $validatedData['reason'],
                'status'             => 'pending',
                'level'              => 1,
                // 'levels'             => json_encode($levels),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            foreach($dates as $item) {
                DB::table('obs_dates')->insertGetId([
                    'obs_application_id' => $applicationID,
                    'date' => $item['date'],
                    'shift'=> $item['shift'],
                ]);
            }

            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                   
                    $path = 'users/' . $employee_no . '/pass-slip-attachments/';
                    $attachmentPath = $file->store($path, 'public');

                    DB::table('obs_attachments')->insert([
                        'obs_applications_id'     => $applicationID,
                        'file_path'  => $attachmentPath,
                        'file_name'  => $file->getClientOriginalName(),
                        'file_type'  => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // foreach ($approvers as $level => $approverList) {
            //     foreach ($approverList as $userId) {
            //         DB::table('obs_approvals')->insertGetId([
            //             'obs_applications_id' => $applicationID,
            //             'user_id'              => $userId,
            //             'level'                => $level,
            //             'status'               => 'pending',
            //         ]);
            //     }
            // }

            $sender = ucwords(Auth::user()->name);
            $payload = [
                'type' => 'application',
                'sender' => $sender,
                'receiver' => 'admins',
                'message' => '%b' . $sender . '%b filed a pass slip application (%bi' . strtoupper($application_no) . ') %bi',
                'link' => url()->route('services.pass_slip.show', ['application' => $applicationID])
            ];
            $this->EventService->pushNotification($payload);

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
            ->addColumn('date', function ($row) {
                $dates = explode('|', $row->dates);
                $newDate = '';

                foreach($dates as $date) {
                    $newDate .= formatDateRanges($date) . '<br>';
                }

                return $newDate;
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
                if (in_array($row->status, ['pending'])) {
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
