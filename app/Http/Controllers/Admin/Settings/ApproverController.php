<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use App\Models\User;
use Carbon\Carbon;

class ApproverController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.approvers.view')->only(['view', 'show']);
        $this->middleware('permission:hr.approvers.create')->only(['create', 'store']);
        $this->middleware('permission:hr.approvers.edit')->only('edit', 'update');
        $this->middleware('permission:hr.approvers.delete')->only('destroy');
    }
    
    public function index(Request $request)
    {   
        
        if ($request->ajax()) {
            $division_id = $request->get('division');
            $unit_id     = $request->get('unit');

            $query = DB::table('application_approver as aa')
                ->leftJoin('application_approver_users as aau', 'aa.id', '=', 'aau.application_approver_id')
                ->leftJoin('divisions as d', 'aa.division_id', '=', 'd.id')
                ->leftJoin('units as u', 'aa.unit_id', '=', 'u.id')
                ->select(
                    'aa.id as approver_id',
                    'aa.type',
                    'aa.division_id',
                    'aa.unit_id',
                    'd.code as division_code',
                    'd.name as division_name',
                    'u.code as unit_code',
                    'u.name as unit_name',
                    'aau.user_id',
                    'aau.level',
                    'aa.created_at',
                    'aa.updated_at'
                )
                ->when($division_id, function ($q) use ($division_id) {
                    $q->where('aa.division_id', $division_id);
                })
                ->when($unit_id, function ($q) use ($unit_id) {
                    $q->where('aa.unit_id', $unit_id);
                })
                ->get()
                ->groupBy('approver_id')
                ->map(function ($group) {
                    $levelCounts = $group
                        ->filter(fn($item) => !is_null($item->user_id))
                        ->groupBy('level')
                        ->map(fn($items) => $items->count());

                    return [
                        'approver_id'   => $group->first()->approver_id,
                        'type'          => $group->first()->type,
                        'division_id'   => $group->pluck('division_id')->unique()->values()->all(),
                        'division_code' => $group->pluck('division_code')->unique()->values()->all(),
                        'division_name' => $group->pluck('division_name')->unique()->values()->all(),
                        'unit_id'       => $group->pluck('unit_id')->unique()->values()->all(),
                        'unit_code'     => $group->pluck('unit_code')->unique()->values()->all(),
                        'unit_name'     => $group->pluck('unit_name')->unique()->values()->all(),
                        'users'         => $group->pluck('user_id')->filter()->unique()->values()->all(),
                        'level_counts'  => $levelCounts->toArray(),
                        'created_at'    => $group->first()->created_at,
                        'updated_at'    => $group->first()->updated_at,
                    ];
                })
                ->values();

            return $this->datatable($query);
        }

        return view('admin.pages.settings.approvers.index');
    }

    public function view() {

        $data = DB::table('application_approver as ap')
            ->leftJoin('units as un', 'ap.unit_id', '=', 'un.id')
            ->leftJoin('application_approver_users as apu', 'ap.id', '=', 'apu.application_approver_id')
            ->leftJoin('employee_information as ei', 'ei.id', '=', 'apu.user_id')
            ->leftJoin('employee_personal as ep', 'ep.employee_no', '=', 'ei.employee_no')
            ->leftJoin('employee_organization as eo', 'eo.employee_no', '=', 'ei.employee_no')
            ->leftJoin('positions as p', 'p.id', '=', 'eo.position_id')
            ->select(
                'ap.type as type',
                'un.name as agency_name',
                'apu.level as level',
                DB::raw("CONCAT(ep.firstname, ' ', ep.lastname) as name"),
                'p.name as position_name',
                'ep.employee_no as employee_no',
                'ep.profile as profile'
            )
            ->whereNotNull('ap.type')
            ->orderBy('ap.id')
            ->orderBy('apu.level')
            ->get();

        // Group by agency and level
        $formatted = [];

        foreach ($data as $row) {
            $agency = $row->agency_name ?: 'Unknown Agency';
            $level = 'Level ' . ($row->level ?: 0);

            // Initialize agency array if not yet set
            if (!isset($formatted[$agency])) {
                $formatted[$agency] = [];
            }

            // Initialize level array under agency if not yet set
            if (!isset($formatted[$agency][$level])) {
                $formatted[$agency][$level] = [];
            }

            if (!is_null($row->profile)) {
                $profile = Storage::url('uploads/employees/' . $employee_no . '/profile/' . $row->profile);
            } else {
                $profile = 'https://ui-avatars.com/api/?name=' 
                    . urlencode($row->name) 
                    . '&background=random&color=fff&font-size=0.5';
            }

            // Add employee under this agency-level
            $formatted[$agency][$level][] = [
                'name' => $row->name ?? 'Unknown',
                'position' => $row->position_name ?? 'N/A',
                'employee_no' => $row->employee_no,
                'profile' => $profile
            ];
        }

        $data = $formatted;
        ksort($data);

        return view('admin.pages.settings.approvers.view', compact('data'));
    }

    public function create() {

        $divisions = DB::table('divisions')->get();
        $users = User::with('roles')
            ->whereDoesntHave('roles', function ($q) {
                $q->whereNotIn('name', ['emp_contractual', 'emp_regular']);
            })
            ->get();

        $usersGrouped = $users->groupBy(function ($user) {
            return $user->roles->pluck('name')->implode(', ') ?: 'No Role';
        });

        $isEdit = false;
        $id = null;
        $units = [];

        return view('admin.pages.settings.approvers.form', compact('divisions', 'units', 'usersGrouped', 'isEdit', 'id'));
    }

    public function store(Request $request)
    {
        $payload = $request->all();

        $this->validate($request, $this->rules($payload));

        DB::beginTransaction();
        
        try {
            if ($payload['type'] === 'payroll') {
                // Handle payroll type: Only one allowed
                $approver = DB::table('application_approver')
                    ->where('type', 'payroll')
                    ->first();

                if ($approver) {
                    // Delete existing users
                    DB::table('application_approver_users')
                        ->where('application_approver_id', $approver->id)
                        ->delete();

                    // Update existing approver
                    DB::table('application_approver')
                        ->where('id', $approver->id)
                        ->update([
                            'division_id' => $payload['division_id'] ?? null,
                            'unit_id'     => $payload['unit_id'] ?? null,
                            'updated_at'  => now(),
                        ]);

                    $approver_id = $approver->id;
                } else {
                    // Insert new if none exists
                    $approver_id = DB::table('application_approver')->insertGetId([
                        'type'        => 'payroll',
                        'division_id' => $payload['division_id'] ?? null,
                        'unit_id'     => $payload['unit_id'] ?? null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            } else {
                // Handle non-payroll types
                $approver = DB::table('application_approver')
                    ->where('type', $payload['type'])
                    ->where('division_id', $payload['division_id'] ?? null)
                    ->where('unit_id', $payload['unit_id'] ?? null)
                    ->first();

                if ($approver) {
                    // Delete existing users
                    DB::table('application_approver_users')
                        ->where('application_approver_id', $approver->id)
                        ->delete();

                    // Update the existing record (optional: update other fields if needed)
                    DB::table('application_approver')
                        ->where('id', $approver->id)
                        ->update([
                            'updated_at' => now(),
                        ]);

                    $approver_id = $approver->id;
                } else {
                    // Insert new
                    $approver_id = DB::table('application_approver')->insertGetId([
                        'type'        => $payload['type'],
                        'division_id' => $payload['division_id'] ?? null,
                        'unit_id'     => $payload['unit_id'] ?? null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            // Insert new approvers (users)
            foreach ($payload['approvers'] as $level => $userIds) {
                foreach ((array) $userIds as $userId) {
                    DB::table('application_approver_users')->insert([
                        'application_approver_id' => $approver_id,
                        'user_id'                 => $userId,
                        'level'                   => $level,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'Approver ' . strtoupper(str_replace('_', ' ', $payload['type'])) . ' ' . ($payload['type'] === 'payroll' ? 'Updated' : 'Saved'),
                'redirect' => route('settings.approvers.create')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function edit(string $id)
    {
        $data = DB::table('application_approver as aa')
            ->leftJoin('application_approver_users as aau', 'aa.id', '=', 'aau.application_approver_id')
            ->leftJoin('divisions as d', 'aa.division_id', '=', 'd.id')
            ->leftJoin('units as u', 'aa.unit_id', '=', 'u.id')
            ->leftJoin('users as usr', 'aau.user_id', '=', 'usr.id')
            ->select(
                'aa.id as approver_id',
                'aa.type',
                'aa.division_id',
                'd.code as division_code',
                'd.name as division_name',
                'aa.unit_id',
                'u.code as unit_code',
                'u.name as unit_name',
                'aau.user_id',
                'aau.level',
                'usr.name as user_name',
                'usr.email as user_email'
            )
            ->where('aa.id', $id)
            ->get()
            ->groupBy('approver_id')
            ->map(function ($group) {
                return [
                    'approver_id'   => $group->first()->approver_id,
                    'type'          => $group->first()->type,
                    'division_id'   => $group->pluck('division_id')->unique()->values()->all(),
                    'division_code' => $group->pluck('division_code')->unique()->values()->all(),
                    'division_name' => $group->pluck('division_name')->unique()->values()->all(),
                    'unit_id'       => $group->pluck('unit_id')->unique()->values()->all(),
                    'unit_code'     => $group->pluck('unit_code')->unique()->values()->all(),
                    'unit_name'     => $group->pluck('unit_name')->unique()->values()->all(),
                    'users'         => $group->filter(fn ($item) => !is_null($item->user_id))
                        ->groupBy('level')
                        ->map(function ($levelGroup) {
                            return $levelGroup->map(fn ($item) => [
                                'id'    => $item->user_id,
                                'name'  => $item->user_name,
                                'email' => $item->user_email,
                            ])->values();
                        })
                        ->toArray(),
                ];
            })
            ->first();

        
        if(is_null($data)) {
            return redirect()->route('settings.approvers.index')
                ->with('error', 'Approver not found');
        }

        $divisions = DB::table('divisions')->get();
        $units = DB::table('units')->where('division_id', $data['division_id'])->get();
        
        $users = User::with('roles')
            ->whereDoesntHave('roles', function ($q) {
                $q->whereNot('name', 'employee');
            })
            ->get();

        $usersGrouped = $users->groupBy(function ($user) {
            return $user->roles->pluck('name')->implode(', ') ?: 'No Role';
        });

        $isEdit = true;

        return view('admin.pages.settings.approvers.form', compact('id', 'divisions', 'units', 'usersGrouped', 'isEdit', 'data'));
    }

    public function rules(array $payload = null, ?int $id = null)
    {
        return [
            'type' => ['required', Rule::in(['overtime', 'leave', 'pass_slip', 'payroll'])],

            'approvers' => ['required', 'array', 'min:1'],
            'approvers.*' => ['required', 'array', 'min:1'],
            'approvers.*.*' => ['required', 'exists:users,id'],

            'division_id' => [
                Rule::requiredIf($payload['type'] !== 'payroll'),
            ],

            'unit_id' => [
                Rule::requiredIf($payload['type'] !== 'payroll'),
            ],
        ];
    }

    public function update(Request $request, $id)
    {

        $payload = $request->all();
        
        $this->validate($request, $this->rules($payload, $id));
        
        DB::beginTransaction();

        try {

            DB::table('application_approver')
                ->where('id', $id)
                ->update([
                    'type'        => $request->type,
                    'division_id' => $request->division_id,
                    'unit_id'     => $request->unit_id,
                    'updated_at'  => now(),
                ]);

            DB::table('application_approver_users')
                ->where('application_approver_id', $id)
                ->delete();

            foreach ($request->approvers as $level => $userIds) {
                foreach ((array) $userIds as $userId) {
                    DB::table('application_approver_users')->insert([
                        'application_approver_id' => $id,
                        'user_id'                 => $userId,
                        'level'                   => $level,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Approver ' . strtoupper(str_replace('_', ' ', $request->name)) . ' Updated',
                'redirect'=> '_self'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy(int $id)
    {
        
        DB::beginTransaction();

        try {

            DB::table('application_approver')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Approver has been deleted.',
                'redirect' => ''
            ]);

        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }

    public function datatable($query)
    {

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('type', function ($row) {
                return str_replace('_', ' ', $row['type']);
            })
            ->editColumn('level_approvers', function ($row) {
                return max(array_keys($row['level_counts'])) . ' levels';
            })
            ->editColumn('no_approvers', function ($row) {
                return DB::table('application_approver_users')
                    ->where('application_approver_id', $row['approver_id'])
                    ->count('user_id') . ' approver(s)';
            })
            ->editColumn('date_created', function ($row) {
                return Carbon::parse($row['created_at'])->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                return '<div class="d-flex gap-2">' .
                    '<a href="' . route('settings.approvers.edit', ['approver' => $row['approver_id']]) . '" 
                        class="btn btn-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fas fa-edit"></i>
                    </a>' .
                    '<button id="btn-delete"
                        class="btn btn-danger btn ms-1 my-1" 
                        data-target="'.route('settings.approvers.destroy', ['approver' => $row['approver_id']]).'"
                        title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                    </button>' .
                '</div>';
                
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

}
