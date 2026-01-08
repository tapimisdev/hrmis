<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if($request->ajax()) {

            $query = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', [
                    'hr_admin',
                    'hr_clerk',
                    'hr_manager',
                    'super_admin'
                ])
                ->select('users.*')
                ->distinct()
                ->get();
            
            return $this->datatable($query); 
        }

        return view('admin.pages.users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isEdit = false;
        $id = null;
        $roles = DB::table('roles')->whereIn('name', [
            'hr_admin',
            'hr_clerk',
            'hr_manager',
            'super_admin'
        ])->get();
    
        return view('admin.pages.users.form', compact('isEdit', 'id', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name'     => 'required|string|max:255',
            'role'     => 'required|exists:roles,id',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'field error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            /** ---------------- Create User ---------------- */
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            /** ---------------- Assign Role ---------------- */
            $role = Role::findById($request->role);
            $user->assignRole($role);

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'User ' . strtoupper($request->name) . ' Added',
                'redirect' => route('users.index'),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        // Find the user
        $user = User::with('roles')->find($id);

        if (!$user) {
            return redirect()->route('users.index');
        }

        // Get the first role assigned (you can adjust for multiple roles)
        $role = $user->roles->first();

        $id = $user->id;
        $isEdit = true;
        $roles = DB::table('roles')->whereIn('name', [
            'hr_admin',
            'hr_clerk',
            'hr_manager',
            'super_admin'
        ])->get();

        return view('admin.pages.users.form', compact('isEdit', 'id' , 'user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name'     => 'required|string|max:255',
            'role'     => 'required|exists:roles,id',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'field error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            // ---------------- Update User ----------------
            $user->name  = $request->name;
            $user->email = $request->email;

            // Only update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // ---------------- Sync Role ----------------
            $role = Role::findById($request->role);
            $user->syncRoles($role); // replaces old roles

            DB::commit();

            return response()->json([
                'status'   => 'success',
                'message'  => 'User ' . strtoupper($request->name) . ' updated successfully',
                'redirect' =>  route('users.edit', ['user' => $user->id]),
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        DB::beginTransaction();

        try {

            // Delete the user (Spatie roles are removed automatically)
            $user->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'User has been deleted.',
                'redirect' => ''
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('name', function ($row) {
                return e($row->name);
            })

            ->addColumn('date_added', function ($row) {
                return Carbon::parse($row->created_at)->format('M d, Y');
            })

            ->addColumn('actions', function ($row) {

                $showRoute   = route('users.show', $row->id);
                $editRoute   = route('users.edit', $row->id);
                $deleteRoute = route('users.destroy', $row->id);

                return '
                    <div class="d-flex flex-wrap gap-1">
                        <a
                            href="' . $editRoute . '"
                            class="btn btn-secondary btn-sm"
                            title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>

                        <button
                            type="button"
                            id="btn-delete"
                            class="btn btn-danger btn-sm"
                            data-target="' . $deleteRoute . '"
                            title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }
}
