<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Enums\EmploymentTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\ModuleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function index(Request $request, $slug) // module slug is unique
    {

        $selectedEmployee = $request->query('employee_no', null);

        $module = DB::table('modules')
            ->where('slug', $slug)
            ->where('isActive', true)
            ->first();

        if (!$module) {
            abort(404);
        }

        $tab_query = DB::table('module_tabs')
            ->where('module_id', $module->id)
            ->where('isActive', true);

        $highest_order = $tab_query->max('order') ?? 0;

        $tabs = $tab_query->get();

        $tab_name = request('tab');

        $store_url = route('modules.store', ['slug' => $slug]);

        if ($tab_name && !$tabs->contains('tab_slug', $tab_name)) {
            abort(404);
        }

        if ($tab_query->count() != 0 && $tab_name == null) {
            $tab_name = $tabs->first()->tab_slug;
        }

        if (request()->wantsJson()) {
            return response()->json([
                'tabs' => $tabs,
            ]);
        }
        

        return view('admin.pages.modules.index', compact('module', 'tabs', 'tab_name', 'store_url', 'slug', 'highest_order', 'selectedEmployee'));
    }

    public function store(ModuleRequest $request, $slug)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {

            $module = $request->module;

            $slug = Str::slug($validatedData['tab_name']);

            DB::table('module_tabs')->insert([
                'module_id' => $module->id,
                'tab_name' => $validatedData['tab_name'],
                'tab_icon' => 'fa-regular fa-file', // default icon
                'tab_slug' => $slug,
                'order' => $validatedData['order'],
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Tab added succesfully', 'status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => 'store failed'], 500);
        }
    }
}
