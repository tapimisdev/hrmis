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
    public function index()
    {
        
        if (request()->ajax()) {
            $query = Role::all();
            return $this->datatable($query);
        }

        return view('admin.pages.settings.role-permission.index');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $permissions = Permission::all();

        $grouped = [];

        foreach ($permissions as $perm) {
            [$action, $module] = explode(' ', $perm['name'], 2);

            $grouped[$module][$action] = $perm;
        }


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

        return redirect()->route('roles.index')->with('success', 'Permissions updated successfully!');
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
