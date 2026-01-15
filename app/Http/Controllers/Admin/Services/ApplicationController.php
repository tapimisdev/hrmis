<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class ApplicationController extends Controller
{

    public function getRawData(string $type, ?int $id = null)
    {
        $user_id = Auth::id();

        /*
        |--------------------------------------------------------------------------
        | Configuration per application type
        |--------------------------------------------------------------------------
        */
        $config = [
            'leave' => [
                'table' => 'leave_applications',
                'alias' => 'la',
                'id_col' => 'id',
                'user_col' => 'user_id',
                'attachment_table' => 'leave_attachments',
                'attachment_fk' => 'leave_application_id',
                'approval_table' => 'leave_approvals',
                'approval_fk' => 'leave_application_id',
                'select_extra' => [
                    'join' => [
                        ['leaves as l', 'la.leave_id', '=', 'l.id'],
                        ['leave_dates as ld', 'ld.leave_application_id', '=', 'la.id'],
                    ],
                    'columns' => [
                        'la.*',
                        'l.name as leave_name',
                        DB::raw('GROUP_CONCAT(DISTINCT ld.date) as dates'),
                        DB::raw("MAX(CONCAT(ep.firstname, ' ', ep.lastname)) as employee_name"),
                    ],
                    'groupBy' => [
                        'la.id',
                        'l.name',
                        'la.name',
                        'la.user_id',
                        'la.employee_no',
                        'la.leave_id',
                        'la.days',
                        'la.reason',
                        'la.status',
                        'la.created_at',
                        'la.updated_at',
                    ],
                ],
            ],
            'obs' => [
                'table' => 'obs_applications',
                'alias' => 'ob',
                'id_col' => 'id',
                'user_col' => 'user_id',
                'attachment_table' => 'obs_attachments',
                'attachment_fk' => 'obs_applications_id',
                'approval_table' => 'obs_approvals',
                'approval_fk' => 'obs_applications_id',
                'select_extra' => [
                    'columns' => [
                        'ob.*',
                        DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as employee_name"),
                    ],
                ],
            ],
            'overtime' => [
                'table' => 'overtime_applications',
                'alias' => 'ot',
                'id_col' => 'id',
                'user_col' => 'user_id',
                'attachment_table' => 'overtime_attachments',
                'attachment_fk' => 'overtime_applications_id',
                'approval_table' => 'overtime_approvals',
                'approval_fk' => 'overtime_applications_id',
                'select_extra' => [
                    'columns' => [
                        'ot.*',
                        DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as employee_name"),
                    ],
                ],
            ],
            'offset' => [
                'table' => 'offset_applications',
                'alias' => 'of',
                'id_col' => 'id',
                'user_col' => 'user_id',
                'attachment_table' => 'offset_attachments',
                'attachment_fk' => 'offset_application_id',
                'approval_table' => 'offset_approvals',
                'approval_fk' => 'offset_application_id',
                'select_extra' => [
                    'join' => [
                        ['offset_dates as od', 'od.offset_application_id', '=', 'of.id'],
                    ],
                    'columns' => [
                        'of.*',
                        DB::raw('GROUP_CONCAT(DISTINCT od.date) as dates'),
                        DB::raw("MAX(CONCAT(ep.firstname, ' ', ep.lastname)) as employee_name"),
                    ],
                    'groupBy' => [
                        'of.id',
                        'of.user_id',
                        'of.employee_no',
                        'of.reason',
                        'of.status',
                        'of.created_at',
                        'of.updated_at',
                    ],
                ],
            ],

        ];

        if (!isset($config[$type])) {
            throw new \InvalidArgumentException("Invalid application type: {$type}");
        }

        $cfg = $config[$type];

        /*
        |--------------------------------------------------------------------------
        | Ensure select_extra exists (FIX FOR OFFSET)
        |--------------------------------------------------------------------------
        */
        $cfg['select_extra'] = array_merge([
            'join'    => [],
            'columns' => [
                "{$cfg['alias']}.*",
                DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as employee_name"),
            ],
            'groupBy' => [],
        ], $cfg['select_extra'] ?? []);

        /*
        |--------------------------------------------------------------------------
        | Fetch main applications
        |--------------------------------------------------------------------------
        */
        $applications = DB::table("{$cfg['table']} as {$cfg['alias']}")
            ->leftJoin('employee_information as ei', 'ei.employee_no', '=', "{$cfg['alias']}.employee_no")
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ei.employee_no');

        foreach ($cfg['select_extra']['join'] as $join) {
            $applications->leftJoin(...$join);
        }

        $applications
            ->select(...$cfg['select_extra']['columns'])
            ->where("{$cfg['alias']}.{$cfg['user_col']}", $user_id)
            ->when($id, fn ($q) => $q->where("{$cfg['alias']}.{$cfg['id_col']}", $id))
            ->orderBy("{$cfg['alias']}.created_at", 'desc');

        if (!empty($cfg['select_extra']['groupBy'])) {
            $applications->groupBy($cfg['select_extra']['groupBy']);
        }

        $applications = $applications->get();
        $applicationIds = $applications->pluck('id')->toArray();

        if (empty($applicationIds)) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        */
        $attachments = collect();
        if ($cfg['attachment_table']) {
            $attachments = DB::table($cfg['attachment_table'])
                ->select($cfg['attachment_fk'], 'file_name', 'file_path', 'file_type')
                ->whereIn($cfg['attachment_fk'], $applicationIds)
                ->get()
                ->groupBy($cfg['attachment_fk']);
        }

        /*
        |--------------------------------------------------------------------------
        | Approvals
        |--------------------------------------------------------------------------
        */
        $approvalsRaw = DB::table("{$cfg['approval_table']} as a")
            ->join('employee_information as ei', 'a.user_id', '=', 'ei.user_id')
            ->join('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->select([
                "a.{$cfg['approval_fk']}",
                'a.user_id',
                'a.level',
                'a.status',
                'ei.employee_no',
                'ep.firstname',
                'ep.lastname',
            ])
            ->whereIn("a.{$cfg['approval_fk']}", $applicationIds)
            ->get();

        $levelApprovals = DB::table($cfg['approval_table'])
            ->select($cfg['approval_fk'], 'level', 'status')
            ->whereIn($cfg['approval_fk'], $applicationIds)
            ->orderBy('level')
            ->get()
            ->groupBy($cfg['approval_fk'])
            ->map(function ($group) {
                return $group->groupBy('level')->map(function ($levelGroup) {
                    foreach ($levelGroup as $row) {
                        if (in_array($row->status, ['approved', 'rejected'])) {
                            return $row->status;
                        }
                    }
                    return $levelGroup->first()->status;
                });
            });

        $groupedApprovals = $approvalsRaw
            ->groupBy('level')
            ->map(fn ($items) => $items->unique('user_id')->values())
            ->sortKeys()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Merge Data
        |--------------------------------------------------------------------------
        */
        return $applications->map(function ($item) use ($cfg, $attachments, $groupedApprovals, $levelApprovals) {
            if (isset($item->dates) && is_string($item->dates)) {
                $item->dates = explode(',', $item->dates);
            }

            if ($cfg['attachment_table']) {
                $item->attachments = $attachments->get($item->id)?->values() ?? [];
            }

            $item->approvals = $groupedApprovals;
            $item->level_approvals = $levelApprovals->get($item->id)?->toArray() ?? [];

            return $item;
        });
    }


    public function getData(string $type)
    {
        $user = Auth::user()->load('employeeInformation');
        $employeeNo = $user->employeeInformation->employee_no ?? null;

        // Get user's organization
        $organization = DB::table('employee_organization')
            ->where('employee_no', $employeeNo)
            ->latest()
            ->first();

        if (!$organization) {
            return [
                'leaves' => collect(),
                'approvers' => collect(),
                'applications' => collect(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Approvers
        |--------------------------------------------------------------------------
        */
        $approvers = DB::table('application_approver as aa')
            ->leftJoin('application_approver_users as aau', 'aa.id', '=', 'aau.application_approver_id')
            ->leftJoin('users as u', 'aau.user_id', '=', 'u.id')
            ->where('aa.type', $type)
            ->where('aa.division_id', $organization->division_id)
            ->where('aa.unit_id', $organization->unit_id)
            ->select(
                'aau.level',
                'u.id as user_id',
                'u.name as user_name'
            )
            ->get()
            ->groupBy('level')
            ->mapWithKeys(function ($items, $level) {
                return [
                    $level => $items
                        ->unique('user_id')
                        ->map(fn ($item) => [
                            'id' => $item->user_id,
                            'name' => $item->user_name,
                        ])
                        ->values(),
                ];
            })
            ->sortKeys();

        /*
        |--------------------------------------------------------------------------
        | Applications (Leave / Offset)
        |--------------------------------------------------------------------------
        */
        if ($type === 'leave') {
            $applicationsQuery = DB::table('leave_applications as a')
                ->join('leave_dates as d', 'a.id', '=', 'd.leave_application_id')
                ->where('a.user_id', $user->id)
                ->select(
                    DB::raw("'leave' as title"),
                    'a.status',
                    'd.date'
                );

            $leaves = DB::table('leaves')
                ->where('is_active', true)
                ->get();
        } else {
            $applicationsQuery = DB::table('offset_applications as a')
                ->join('offset_dates as d', 'a.id', '=', 'd.offset_application_id')
                ->where('a.user_id', $user->id)
                ->select(
                    DB::raw("'offset' as title"),
                    'a.status',
                    'd.date'
                );

            $leaves = collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Holidays & Suspensions (Shared)
        |--------------------------------------------------------------------------
        */
        $holidaysQuery = DB::table('holidays')
            ->select(
                'name as title',
                DB::raw("'holiday' as status"),
                'date'
            );

        $suspensionsQuery = DB::table('suspension as s')
            ->join('suspension_dates as sd', 's.id', '=', 'sd.suspension_id')
            ->where('s.isActive', true)
            ->select(
                's.name as title',
                DB::raw("'suspension' as status"),
                'sd.date'
            );

        $applications = $applicationsQuery
            ->unionAll($holidaysQuery)
            ->unionAll($suspensionsQuery)
            ->orderBy('date')
            ->get();

        return [
            'leaves' => $leaves,
            'approvers' => $approvers,
            'applications' => $applications,
        ];
    }



}