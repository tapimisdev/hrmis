<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RolesAndPermissionController extends Controller
{
    public function index()
    {
        $query = Role::all();
        
        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('admin.pages.settings.role-permission.index');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();

        return view('admin.pages.settings.role-permission.edit', compact('role', 'permissions'));
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
                <button data-id="' . $row->id . '" 
                        class="btn btn-outline-primary btn-sm ms-1 delete-button" 
                        title="Update Permissions">
                    <i class="fa-solid fa-key"></i>
                </button>
                <a href="' . route('role-and-permission.edit', $row->id) . '" 
                class="btn btn-outline-secondary btn-sm ms-1" 
                title="Edit">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

}
