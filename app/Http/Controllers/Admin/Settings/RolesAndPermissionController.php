<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RolesAndPermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.role_and_permission.view')->only(['index', 'show']);
        $this->middleware('permission:hr.role_and_permission.create')->only(['create', 'store']);
        $this->middleware('permission:hr.role_and_permission.edit')->only('edit', 'update', 'updateRole', 'editRole');
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Role::where('name', '!=', 'super_admin')->get();
            return $this->datatable($query);
        }

        return view('admin.pages.settings.role-permission.index');
    }

    public function create()
    {
        return view('admin.pages.settings.role-permission.create');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $prefix = strstr($role->name, '_', true) . '.';

        $permissions = Permission::where('name', 'like', $prefix.'%')->get();

        $grouped = [];

        foreach ($permissions as $perm) {
            // Split permission name into [action, module]
            $parts = explode('.', $perm->name, 3);

            if (count($parts) === 3) {
                [$hr, $module, $action] = $parts;
            } else {
                // Handle permissions without module part
                $hr = 'hr';
                $module = $parts[0];
                $action = 'genera';
            }

            // Group them like: $grouped['hris']['view'] = Permission(...)
            $grouped[$module][$action] = $perm;
            $action = str_replace('_', ' ', $action);
            $perm['short_name'] = $action;
        }

        // Sort modules alphabetically for clean display (optional)
        ksort($grouped);

        return view('admin.pages.settings.role-permission.edit', compact('role', 'grouped'));
    }

    public function editRole($id)
    {
        $role = Role::findOrFail($id);
        return response(['data' => $role, 'message' => 'success'], 200);
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        DB::beginTransaction();
        try {
    
            $role->name = $request->name;
            $role->save();
            DB::commit();

            return response(['data' => $role, 'message' => 'update success'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response(['data' => $e->getMessage(), 'message' => 'update role failed'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // validate
        $request->validate([
            'permissions' => 'array'
        ]);

        // sync permissions
        $role->syncPermissions($request->permissions ?? []);

        return response(['message' => 'permission updated succesfully!'], 200);
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('actions', function ($row) {
               return '
                <div class="d-block d-md-flex gap-2 justify-content-start">
                    <a href="' . route('role-and-permission.edit', $row->id) . '" 
                    class="btn btn-primary btn ms-1 my-1" 
                    title="Edit">
                    <i class="fa-solid fa-key"></i>
                    </a>
                </div>    
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

}
