<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class ApprovalsController extends Controller
{

    public function getLevels(string $type, bool $forApproval = false, int $id = null)
    {
        $user_id = Auth::id();

        $mapping = [
            'leave'    => ['table' => 'leave_approvals', 'column' => 'leave_application_id'],
            'overtime' => ['table' => 'overtime_approvals', 'column' => 'overtime_applications_id'],
            'pass_slip'      => ['table' => 'obs_approvals', 'column' => 'obs_applications_id'],
        ];

        if (!isset($mapping[$type])) {
            throw new \InvalidArgumentException("Invalid approval type: {$type}");
        }

        $table = $mapping[$type]['table'];
        $column = $mapping[$type]['column'];

        // Build query
        $query = DB::table($table)
            ->when(!$forApproval, function ($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            }, function ($query) use ($column, $id) {
                return $query->where($column, $id);
            });

        return $query->distinct()->pluck('level') ?? [];
    }

    public function getData(string $type, $level = null, int $id = null, bool $showPendingOnly = false)
    {
        $user_id = Auth::id();

        // Configuration map for all approval types
        $mapping = [
            'leave' => [
                'table' => 'leave_approvals',
                'alias' => 'la',
                'join_table' => 'leave_applications',
                'join_alias' => 'ob',
                'foreign_key' => 'leave_application_id',
                'attachments' => 'leave_attachments',
                'dates' => 'leave_dates',
                'select' => [
                    'ob.id',
                    'ob.application_no',
                    'ob.name as application_name',
                    'ob.user_id',
                    'ob.employee_no',
                    'ob.days',
                    'ob.reason',
                    'ob.status',
                    'ob.remarks',
                    'ob.level',
                    'ob.created_at',
                    'ep.firstname',
                    'ep.lastname',
                    'la.status as level_status',
                ],
            ],
            'overtime' => [
                'table' => 'overtime_approvals',
                'alias' => 'oa',
                'join_table' => 'overtime_applications',
                'join_alias' => 'ob',
                'foreign_key' => 'overtime_applications_id',
                'attachments' => null,
                'dates' => null,
                'select' => [
                    'ob.id',
                    'ob.application_no',
                    'ob.user_id',
                    'ob.employee_no',
                    'ob.date',
                    'ob.start_time',
                    'ob.end_time',
                    'ob.total_hours',
                    'ob.reason',
                    'ob.status',
                    'ob.remarks',
                    'ob.level',
                    'ob.created_at',
                    'ep.firstname',
                    'ep.lastname',
                    'oa.status as level_status',
                ],
            ],
            'pass_slip' => [
                'table' => 'obs_approvals',
                'alias' => 'oa',
                'join_table' => 'obs_applications',
                'join_alias' => 'ob',
                'foreign_key' => 'obs_applications_id',
                'attachments' => 'obs_attachments',
                'dates' => null,
                'select' => [
                    'ob.id',
                    'ob.application_no',
                    'ob.user_id',
                    'ob.employee_no',
                    'ob.date_from',
                    'ob.date_to',
                    'ob.time_out',
                    'ob.time_in',
                    'ob.destination',
                    'ob.purpose',
                    'ob.mode_of_transport',
                    'ob.estimated_expense',
                    'ob.charge_to',
                    'ob.status',
                    'ob.remarks',
                    'ob.level',
                    'ob.created_at',
                    'ep.firstname',
                    'ep.lastname',
                    'oa.status as level_status',
                ],
            ],
        ];

        // Validate type
        if (!isset($mapping[$type])) {
            throw new \InvalidArgumentException("Invalid application type: {$type}");
        }

        // Extract config
        $config = $mapping[$type];
        $table = $config['table'];
        $alias = $config['alias'];
        $joinTable = $config['join_table'];
        $joinAlias = $config['join_alias'];
        $foreignKey = $config['foreign_key'];

        // Build base query
        $query = DB::table("{$table} as {$alias}")
            ->leftJoin("{$joinTable} as {$joinAlias}", "{$joinAlias}.id", '=', "{$alias}.{$foreignKey}")
            ->leftJoin('employee_personal as ep', "{$joinAlias}.employee_no", '=', 'ep.employee_no')
            ->select($config['select'])
            ->where("{$alias}.user_id", $user_id)
            ->when(!is_null($level) && is_numeric($level), function ($query) use ($alias, $joinAlias, $level) {
                return $query->where("{$alias}.level", $level)
                            ->whereColumn("{$alias}.level", "{$joinAlias}.level");
            })
            ->when(!empty($showPendingOnly) && $showPendingOnly === true, function ($query) use ($alias) {
                return $query->where("{$alias}.status", 'pending');
            });

        // Fetch a single record if $id is provided
        if ($id !== null) {
            $query->where("{$joinAlias}.id", $id);
            $item = $query->first();

            if (!$item) {
                return null;
            }

            $extra = [];

            if ($config['attachments']) {
                $extra['attachments'] = DB::table($config['attachments'])
                    ->where("{$foreignKey}", $item->id)
                    ->get();
            }

            if ($config['dates']) {
                $extra['dates'] = DB::table($config['dates'])
                    ->where("{$foreignKey}", $item->id)
                    ->get();
            }

            return (object) array_merge((array) $item, $extra);
        }

        // Fetch all records
        $data = $query->get();

        $applicationIds = $data->pluck('id');
        $attachments = collect();
        $dates = collect();

        if ($config['attachments']) {
            $attachments = DB::table($config['attachments'])
                ->whereIn($foreignKey, $applicationIds)
                ->get()
                ->groupBy($foreignKey);
        }

        if ($config['dates']) {
            $dates = DB::table($config['dates'])
                ->whereIn($foreignKey, $applicationIds)
                ->get()
                ->groupBy($foreignKey);
        }

        // Merge attachments and dates
        return $data->map(function ($item) use ($attachments, $dates, $foreignKey) {
            return (object) array_merge((array) $item, [
                'attachments' => $attachments->get($item->id)?->values() ?? [],
                'dates' => $dates->get($item->id)?->values() ?? [],
            ]);
        });
    }



}