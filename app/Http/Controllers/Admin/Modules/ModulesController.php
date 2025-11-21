<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Modules\ModuleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModulesController extends Controller
{
    public function index($slug) // module slug is unique
    {
        $module = DB::table('modules')
                    ->where('slug', $slug)
                    ->where('isActive', true)
                    ->first();
        
        if(!$module) {
            abort(404);
        }

        $tabs = DB::table('module_tabs')
                ->where('module_id', $module->id)
                ->where('isActive', true)
                ->get();

        $tab_name = request('tab');

        $store_url = route('modules.store', ['slug' => $slug]);

        if($tab_name && !$tabs->contains('tab_slug', $tab_name)) {
            abort(404);
        }

        if(request()->wantsJson()) {
            return response()->json([
                'tabs' => $tabs,
            ]);
        }

        return view('admin.pages.modules.index', compact('module', 'tabs', 'tab_name', 'store_url', 'slug'));
    }

    public function store(ModuleRequest $request, $slug) {

        $validatedData = $request->validated();

        DB::beginTransaction();

        try {

            $module = $request->module;

            $lowercaseSlug = strtolower($validatedData['tab_slug']);
            
            DB::table('module_tabs')->insert([
                'module_id' => $module->id,
                'tab_name' => $validatedData['tab_name'],
                'tab_icon' => 'fa-regular fa-file', // default icon
                'tab_slug' => $lowercaseSlug,
                'order' => $validatedData['order'],
                'isActive' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Store method called', 'status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => 'store failed'], 500);
        }
    }
    
}
