<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ObsApprovalController extends Controller
{

    public function getLevels(bool $forApproval = false, int $id = null)
    {
        $user_id = Auth::id();

        $query = DB::table('obs_approvals')
            ->when(!$forApproval, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            }, function ($query) use ($id) {
                return $query->where('obs_id', $id);
            });

        return $query->pluck('level') ?? [];
    }

    public function getRawData(int $level, int $id = null)
    {
        $user_id = Auth::id();

        $query = DB::table('obs_approvals')
            ->leftJoin('obs', 'obs.id', '=', 'obs_approvals.obs_id')
            ->leftJoin('employee_personal as ep', 'obs.user_id', '=', 'ep.user_id')
            ->select(
                'obs.id',
                'obs.obs_no',
                'obs.user_id',
                'obs.date_from',
                'obs.date_to',
                'obs.time_out',
                'obs.time_in',
                'obs.destination',
                'obs.purpose',
                'obs.mode_of_transport',
                'obs.estimated_expense',
                'obs.charge_to',
                'obs.status',
                'obs.remarks',
                'obs.level',
                'obs.created_at',
                'obs.approved_at',
                'ep.firstname',
                'ep.lastname'
            )
            ->where('obs_approvals.user_id', $user_id)
            ->where('obs_approvals.level', $level)
            ->whereColumn('obs_approvals.level', '=', 'obs.level');

        if ($id !== null) {
            $query->where('obs.id', $id);
            $item = $query->first();

            if (!$item) {
                return null;
            }

            // Get approvals grouped by level with their statuses
            $approvals = DB::table('obs_approvals')
                ->where('obs_id', $item->id)
                ->get()
                ->groupBy('level')
                ->map(function ($group) {
                    return $group->map(function ($approval) {
                        return (object)[
                            'user_id' => $approval->user_id,
                            'status' => $approval->status,
                            'remarks' => $approval->remarks,
                            'action_at' => $approval->action_at,
                        ];
                    })->toArray();
                })->toArray();

            $attachments = DB::table('obs_attachments')
                ->where('obs_id', $item->id)
                ->get();

            return (object)[
                'id' => $item->id,
                'obs_no' => $item->obs_no,
                'firstname' => $item->firstname,
                'lastname' => $item->lastname,
                'user_id' => $item->user_id,
                'date_from' => $item->date_from,
                'date_to' => $item->date_to,
                'time_out' => $item->time_out,
                'time_in' => $item->time_in,
                'destination' => $item->destination,
                'purpose' => $item->purpose,
                'mode_of_transport' => $item->mode_of_transport,
                'estimated_expense' => $item->estimated_expense,
                'charge_to' => $item->charge_to,
                'status' => $item->status,
                'remarks' => $item->remarks,
                'level' => $item->level,
                'created_at' => $item->created_at,
                'approved_at' => $item->approved_at,
                'approvals' => $approvals,
                'attachments' => $attachments,
            ];
        }

        $data = $query->get();

        $data = $data->map(function ($item) {
            $approvals = DB::table('obs_approvals')
                ->where('obs_id', $item->id)
                ->get()
                ->groupBy('level')
                ->map(function ($group) {
                    return $group->map(function ($approval) {
                        return (object)[
                            'user_id' => $approval->user_id,
                            'status' => $approval->status,
                            'remarks' => $approval->remarks,
                            'action_at' => $approval->action_at,
                        ];
                    })->toArray();
                })->toArray();

            $attachments = DB::table('obs_attachments')
                ->where('obs_id', $item->id)
                ->get();

            return (object)[
                'id' => $item->id,
                'obs_no' => $item->obs_no,
                'user_id' => $item->user_id,
                'date_from' => $item->date_from,
                'date_to' => $item->date_to,
                'time_out' => $item->time_out,
                'time_in' => $item->time_in,
                'destination' => $item->destination,
                'purpose' => $item->purpose,
                'mode_of_transport' => $item->mode_of_transport,
                'estimated_expense' => $item->estimated_expense,
                'charge_to' => $item->charge_to,
                'status' => $item->status,
                'remarks' => $item->remarks,
                'level' => $item->level,
                'created_at' => $item->created_at,
                'approved_at' => $item->approved_at,
                'approvals' => $approvals,
                'attachments' => $attachments,
            ];
        });

        return $data;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if (request()->ajax()) {
            $data = $this->getRawData($level);
            return $this->datatable($level, $data);
        }

        $levels = $this->getLevels(false)->toArray() ?? [];

        if (!empty($levels) && is_null($level) || !in_array($level, $levels)) {
            return redirect()->route('approval-obs.index', ['level' => $levels[0]]);
        }

        return view('employee.pages.obs-approval.index');
    }

    public function show(int $level, int $id) {

        $data = $this->getRawData($level, $id) ?? [];

        if(!$data) {
            return redirect()->back();
        }

        return view('employee.pages.leave-obs.show', compact('id', 'data'));
    }

    public function datatable($level, $query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', function($row) {
                return $row->name;
            })
            ->addColumn('date', function ($row) {
                $datesString = $row->dates->pluck('date')->toArray() ?? [];
                return formatDateRanges($datesString);
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

                return '<span class="badge rounded-pill bg-' . $badgeClass . '">' . ucfirst($status)  . '</span>';
            })
            ->addColumn('actions', function ($row) use ($level) {
                $buttons = '
                    <div class="d-flex align-items-center">
                         <a href="'.route('approval-leave.show', ['level' => $level, 'id' => $row->id]).'" 
                            class="btn btn-outline-primary btn show-button ms-1 my-1" 
                            title="Show">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                ';
                $buttons .= '</div>';

                return $buttons;
            })
            ->rawColumns(['actions', 'status', 'date'])
            ->make(true);
    }
}
