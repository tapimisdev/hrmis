<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Services\ApplicationController;
use App\Http\Controllers\Admin\Services\OffsetApplicationController as UpdateCreditsController;
use App\Http\Requests\Employee\StoreOffsetApplication;
use App\Events\NotificationEvents;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
use Carbon\Carbon;

class OffsetApplicationController extends Controller
{
    protected $applicationService;
    protected $EventService;

    public function __construct(ApplicationController $applicationService, EventService $EventService)
    {
        $this->middleware('permission:emp.offset_application.view|hr.offset_approval.view')
            ->only(['index', 'create', 'show']);

        $this->middleware(
            'permission:emp.offset_application.apply|hr.offset_approval.save'
        )->only(['store']);

        $this->applicationService = $applicationService;
        $this->EventService = $EventService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {

            $data = $this->applicationService->getRawData('offset');

            return $this->datatable($data);
        }

        return view('employee.pages.offset.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $myId = Auth::id();
        $data = $this->applicationService->getData(['leave', 'offset', 'obs', 'special_order']);

        // $approvers = $data['approvers'];
        // $approvers = $approvers->map(function ($collection) use ($myId) {
        //     return $collection->reject(function ($approver) use ($myId) {
        //         return $approver['id'] === $myId;
        //     })->values();
        // });

        $applications = $data['applications'];
        
        return view('employee.pages.offset.create', compact('applications'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOffsetApplication $request) 
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

            $credit_equivalent = 0;

            foreach($dates as $date) {
                if ($date['shift'] === 'wholeday') {
                    $credit_equivalent += 1;
                } else {
                    $credit_equivalent += 0.5;
                }
            }

            // $data = $this->applicationService->getData('offset');
            // $levels = array_keys($data['approvers']->toArray() ?? []) ?? [];
            // $approvers = $validatedData['approvers'];

            $leaveName = 'Offset Leave';

            $application_no = generateApplicationNo('offset_applications', 'OF');

            $applicationID = DB::table('offset_applications')->insertGetId([
                'application_no' => $application_no,
                'user_id'       => $user_id,
                'name'          => $leaveName,
                'employee_no'   => $employee_no,
                'credit_equivalent' => number_format($credit_equivalent, 2),
                'reason'        => $validatedData['reason'],
                'status'        =>  $isDirectlyApproved ? 'approved' : 'pending',
                'actioned_by'   => $isDirectlyApproved ? Auth::id() : null,
                'level'         => 1,
                // 'levels'        => json_encode($levels),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        
            foreach($dates as $item) {
                DB::table('offset_dates')->insertGetId([
                    'offset_application_id' => $applicationID,
                    'date' => $item['date'],
                    'shift'=> $item['shift'],
                    'credit_equivalent' => $item['shift'] === 'wholeday' ? 1 : 0.5,
                ]);
            }

            // foreach ($approvers as $level => $approverList) {
            //     foreach ($approverList as $userId) {
            //         DB::table('offset_approvals')->insertGetId([
            //             'offset_application_id' => $applicationID,
            //             'user_id'              => $userId,
            //             'level'                => $level,
            //             'status'               => 'pending',
            //         ]);
            //     }
            // }


            // Handle multiple attachments (if any)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    
                    $path = 'users/' . $employee_no . '/offset-attachments/';
                    $attachmentPath = $file->store($path, 'public');

                    DB::table('offset_attachments')->insert([
                        'offset_application_id' => $applicationID,
                        'file_path'            => $attachmentPath,
                        'file_name'            => $file->getClientOriginalName(),
                        'file_type'            => $file->getMimeType(),
                    ]);
                }
            }

            if($isDirectlyApproved) {
                app(UpdateCreditsController::class)->updateCredits($applicationID);
            }

            if(!$isDirectlyApproved) {
                $sender = ucwords(Auth::user()->name);
                $payload = [
                    'type' => 'event',
                    'sender' => $sender,
                    'receiver' => 'admins',
                    'message' => '%b' . $sender . '%b filed an offset application (%bi' . strtoupper($application_no) . ') %bi',
                    'link' => url()->route('services.offset.show', ['application' => $applicationID])
                ];
                $this->EventService->pushNotification($payload);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Offset application has been submitted',
                'redirect' => route('offset.create')
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
        $data = $this->applicationService->getRawData('offset', $id)[0] ?? [];

        if(!$data) {
            return redirect()->route('offset.index');
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
            $affected = DB::table('offset_applications')
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
            ->editColumn('leave', function($row) {
                return $row->name;
            })
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
            ->addColumn('status_badge', function ($row) {
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
            ->rawColumns(['actions', 'status_badge', 'date'])
            ->make(true);
    }
}
