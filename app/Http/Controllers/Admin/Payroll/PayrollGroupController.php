<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PayrollGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $query = DB::table('payroll_groups')
            ->where('payroll_groups.is_active', true)
            ->leftJoin('employment_types', 'payroll_groups.employment_type_id', '=', 'employment_types.id')
            ->leftJoin('payroll_group_employees', 'payroll_groups.id', '=', 'payroll_group_employees.payroll_group_id')
            ->select(
                'payroll_groups.id',
                'payroll_groups.name',
                'employment_types.name as employment_type_name',
                'payroll_groups.remarks',
                DB::raw('COUNT(payroll_group_employees.employee_no) as employee_count')
            )
            ->groupBy(
                'payroll_groups.id',
                'payroll_groups.name',
                'employment_types.name',
                'payroll_groups.remarks'
            )
            ->get();


        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('admin.pages.payroll.payroll-group.index');
    }

    public function create()
    {
        $employmentTypes = DB::table('employment_types')
            ->orderBy('name')
            ->get();

        return view('admin.pages.payroll.payroll-group.create', compact('employmentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employment_type_id' => ['required', 'integer', 'exists:employment_types,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $payroll_group_id = DB::table('payroll_groups')->insertGetId([
                'name' => $validated['name'],
                'employment_type_id' => $validated['employment_type_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Group is succcesfully created!',
                'redirect' => route('payroll.group.employees.index', [
                    'id' => $payroll_group_id
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $group = DB::table('payroll_groups')
            ->leftJoin('employment_types', 'payroll_groups.employment_type_id', '=', 'employment_types.id')
            ->where('payroll_groups.id', $id)
            ->where('payroll_groups.is_active', true)
            ->select(
                'payroll_groups.id',
                'payroll_groups.name',
                'payroll_groups.remarks',
                'payroll_groups.employment_type_id',
                'employment_types.name as employment_type_name'
            )
            ->first();

        if (!$group) {
            abort(404, 'Payroll group not found.');
        }

        $employeeCount = DB::table('payroll_group_employees')
            ->where('payroll_group_id', $id)
            ->count();

        return response()->json([
            'payroll_group' => $group,
            'employee_count' => $employeeCount,
        ], 200);
    }

    public function edit($id)
    {
        $group = DB::table('payroll_groups')
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$group) {
            abort(404, 'Payroll group not found.');
        }

        $employmentTypes = DB::table('employment_types')
            ->orderBy('name')
            ->get();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'payroll_group' => $group,
                'employment_types' => $employmentTypes,
            ], 200);
        }

        return view('admin.pages.payroll.payroll-group.edit', compact('group', 'employmentTypes'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employment_type_id' => ['nullable', 'integer', 'exists:employment_types,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::beginTransaction();
        try {
            $group = DB::table('payroll_groups')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$group) {
                abort(404, 'Payroll group not found.');
            }

            DB::table('payroll_groups')->where('id', $id)->update([
                'name' => $validated['name'],
                'employment_type_id' => $validated['employment_type_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll Group Updated',
                'redirect' => '',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $group = DB::table('payroll_groups')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$group) {
                abort(404, 'Payroll group not found.');
            }

            DB::table('payroll_groups')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll Group deleted successfully.',
                'redirect' => '_self',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error Occured: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('employee_count', function ($row) {
                return '<span class="badge bg-info">'
                    . ($row->employee_count ?? 0)
                    . ' employees</span>';
            })

            ->addColumn('actions', function ($row) {
                return '<div class="d-flex">' .

                    // Manage Button
                    '<button data-id="' . $row->id . '" 
                        class="btn btn-primary btn ms-1 my-1 manage-button" 
                        title="Manage">
                        <i class="fas fa-cogs"></i>
                    </button>' .

                    // Edit Button
                    '<a href="' . route('payroll.group.edit', $row->id) . '" 
                        data-id="' . $row->id . '" 
                        class="btn btn-secondary btn ms-1 my-1" 
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>' .

                    // Delete Button
                    '<button data-id="' . $row->id . '" 
                        class="btn btn-danger btn ms-1 my-1 delete-button" 
                        title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>' .

                '</div>';
            })
            ->rawColumns(['employee_count', 'actions'])


            ->rawColumns(['employee_count', 'actions'])
            ->make(true);
    }
}
