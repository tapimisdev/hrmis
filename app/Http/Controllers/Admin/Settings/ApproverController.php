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
    
    public function index(Request $request)
    {   
        if ($request->ajax()) {

            $division_id = $request->get('division');
            $unit_id     = $request->get('unit');

            $query = DB::table('application_approver as aa')
                ->leftJoin('application_approver_user as aau', 'aa.id', '=', 'aau.application_approver_id')
                ->leftJoin('application_approver_org as aao', 'aa.id', '=', 'aao.application_approver_id')
                ->leftJoin('divisions as d', 'aao.division_id', '=', 'd.id')
                ->leftJoin('units as u', 'aao.unit_id', '=', 'u.id')
                ->select(
                    'aa.id as approver_id',
                    'aa.name',
                    'aa.description',
                    'aa.type',
                    'aao.division_id',
                    'aao.unit_id',
                    'd.code as division_code',
                    'd.name as division_name',
                    'u.code as unit_code',
                    'u.name as unit_name',
                    'aau.user_id',
                    'aa.created_at',
                    'aa.updated_at'
                )
                ->when($division_id, function ($q) use ($division_id) {
                    $q->where('aao.division_id', $division_id);
                })
                ->when($unit_id, function ($q) use ($unit_id) {
                    $q->where('aao.unit_id', $unit_id);
                })
                ->get()
                ->groupBy('approver_id')
                ->map(function ($group) {
                    return [
                        'approver_id'   => $group->first()->approver_id,
                        'name'          => $group->first()->name,
                        'description'   => $group->first()->description,
                        'type'          => $group->first()->type,
                        'division_id'   => $group->pluck('division_id')->unique()->values()->all(),
                        'division_code' => $group->pluck('division_code')->unique()->values()->all(),
                        'division_name' => $group->pluck('division_name')->unique()->values()->all(),
                        'unit_id'       => $group->pluck('unit_id')->unique()->values()->all(),
                        'unit_code'     => $group->pluck('unit_code')->unique()->values()->all(),
                        'unit_name'     => $group->pluck('unit_name')->unique()->values()->all(),
                        'users'         => $group->pluck('user_id')->filter()->unique()->values()->all(), 
                        'created_at'    => $group->first()->created_at,
                        'updated_at'    => $group->first()->updated_at,
                    ];
                })
                ->values();
            
            return $this->datatable($query);
        }

        return view('admin.pages.settings.approvers.index');
    }


    public function create() {

        $divisions = DB::table('divisions')->get();
        $users = User::whereDoesntHave('roles', function ($q) {
                $q->where('name', 'employee');
            })->get();


        $isEdit = false;
        $id = null;
        $units = [];

        return view('admin.pages.settings.approvers.form', compact('divisions', 'units', 'users', 'isEdit', 'id'));
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->rules('store'));


        DB::beginTransaction();

        try {

            $approver_id = DB::table('application_approver')->insertGetId([
                'type'        => $request->type,
                'name'        => $request->name,
                'description' => $request->description,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            foreach ($request->unit_id as $unit_id) {
                DB::table('application_approver_org')->insert([
                    'application_approver_id' => $approver_id,
                    'division_id'             => $request->division_id,
                    'unit_id'                 => $unit_id,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }

            foreach ($request->approvers as $level => $userIds) {
                foreach ((array) $userIds as $userId) {
                    DB::table('application_approver_user')->insert([
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
                'status'  => 'success',
                'message' => 'Approver ' . strtoupper($request->name) . ' Added',
                'redirect'=> '_reload'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occured: ' . $e->getMessage()
            ]);
        }
    }
 

    public function edit(string $id)
    {

        $data = DB::table('application_approver as aa')
            ->leftJoin('application_approver_user as aau', 'aa.id', '=', 'aau.application_approver_id')
            ->leftJoin('application_approver_org as aao', 'aa.id', '=', 'aao.application_approver_id')
            ->leftJoin('divisions as d', 'aao.division_id', '=', 'd.id')
            ->leftJoin('units as u', 'aao.unit_id', '=', 'u.id')
            ->leftJoin('users as usr', 'aau.user_id', '=', 'usr.id')
            ->select(
                'aa.id as approver_id',
                'aa.type',
                'aa.name',
                'aa.description',
                'aao.division_id',
                'd.code as division_code',
                'd.name as division_name',
                'aao.unit_id',
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
                    'name'          => $group->first()->name,
                    'description'   => $group->first()->description,
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
        
        $users     = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'employee');
        })->get();

        $isEdit = true;

        return view('admin.pages.settings.approvers.form', compact('id', 'divisions', 'units', 'users', 'isEdit', 'data'));
    }

    public function rules(string $type, ?int $id = null)
    {
        $rules = [
            'name'        => $type === 'store' ? 'required|string|max:255' : 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:overtime,leave,pass_slip',
            'approvers'   => 'required|array|min:1',
            'approvers.*' => 'required|array|min:1',
            'approvers.*.*' => 'required|exists:users,id',
            'division_id' => 'required|exists:divisions,id',
            'unit_id'     => 'required|array|min:1',
        ];

        $rules['unit_id.*'] = [
            'required',
            'exists:units,id',
            function ($attribute, $value, $fail) use ($type, $id) {
                $division_id = request('division_id');
                $typeValue   = request('type');

                $query = DB::table('application_approver_org')
                    ->where('unit_id', $value)
                    ->where('division_id', $division_id)
                    ->where('type', $typeValue);

                if ($type === 'update' && $id) {
                    $query->where('application_approver_id', '!=', $id);
                }

                if ($query->exists()) {
                    $fail("The selected unit {$value} already exists for this division and type.");
                }
            },
        ];

        return $rules;
    }


    public function update(Request $request, $id)
    {

        $this->validate($request, $this->rules('update', $id));

        DB::beginTransaction();

        try {

            DB::table('application_approver')
                ->where('id', $id)
                ->update([
                    'type'        => $request->type,
                    'name'        => $request->name,
                    'description' => $request->description,
                    'updated_at'  => now(),
                ]);

            DB::table('application_approver_org')
                ->where('application_approver_id', $id)
                ->delete();

            foreach ($request->unit_id as $unit_id) {
                DB::table('application_approver_org')->insert([
                    'application_approver_id' => $id,
                    'division_id'             => $request->division_id,
                    'unit_id'                 => $unit_id,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ]);
            }

            DB::table('application_approver_user')
                ->where('application_approver_id', $id)
                ->delete();

            foreach ($request->approvers as $level => $userIds) {
                foreach ((array) $userIds as $userId) {
                    DB::table('application_approver_user')->insert([
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
                'message' => 'Approver ' . strtoupper($request->name) . ' Updated',
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


    public function datatable($query)
    {

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('type', function ($row) {
                return $row['type'];
            })
            ->editColumn('no_approvers', function ($row) {
                return DB::table('application_approver_user')
                    ->where('application_approver_id', $row['approver_id'])
                    ->count('user_id');
            })
            ->editColumn('date_created', function ($row) {
                return Carbon::parse($row['created_at'])->format('M d, Y');
            })
            ->addColumn('actions', function ($row) {
                return '<div class="d-flex gap-2">' .
                    '<button data-id="' . $row['approver_id'] . '" class="btn btn-outline-primary btn ms-1 my-1 show-button" title="View">' .
                        '<i class="fas fa-eye"></i>' .
                    '</button>' .
                    '<a href="' . route('settings.approvers.edit', ['approver' => $row['approver_id']]) . '" 
                        class="btn btn-outline-secondary btn ms-1 my-1" 
                        title="Edit">
                            <i class="fas fa-edit"></i>
                    </a>' .
                    '<button id="btn-delete"
                        class="btn btn-outline-danger btn ms-1 my-1" 
                        data-target="'.route('settings.approvers.destroy', ['approver' => $row['approver_id']]).'"
                        title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                    </button>' .
                '</div>';
                
            })
            ->rawColumns(['actions', 'is_taxable'])
            ->make(true);
    }

}
