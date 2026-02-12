<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollComponentsController extends Controller
{
    public function index(Request $request)
    {
        if($request->wantsJson()) {
            $data = DB::table('payroll_components')
                ->get();
            return response()->json([
                'data' => $data
            ]);

        }

        return view('admin.pages.settings.payroll-components.index');
    }

    public function create() {

        return view('admin.pages.settings.payroll-components.form');

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'icon' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:payroll_components',
            'slug' => 'required|string|max:255|unique:payroll_components',
            'type' => 'required|in:earnings,taxes',
        ]);

        try {
            DB::beginTransaction();

            $id = DB::table('payroll_components')->insertGetId([
                'icon' => $validated['icon'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'type' => $validated['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll component created successfully',
                'redirect' => route('payroll-components.index', ['slug' => $validated['slug']]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating payroll component: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:payroll_components,id',
            'icon' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:payroll_components,slug,' . $id,
            'type' => 'required|in:earnings,taxes',
        ]);

        try {
            DB::beginTransaction();

            DB::table('payroll_components')
                ->where('id', $id)
                ->update([
                    'icon' => $validated['icon'],
                    'name' => $validated['name'],
                    'slug' => $validated['slug'],
                    'type' => $validated['type'],
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll component updated successfully',
                'redirect' => route('settings.payroll-components.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating payroll component: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            DB::table('payroll_components')->where('id', $id)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll component deleted successfully',
                'redirect' => url()->current(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting payroll component: ' . $e->getMessage(),
            ], 500);
        }
    }



}
