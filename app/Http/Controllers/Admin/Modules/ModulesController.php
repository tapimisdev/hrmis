<?php

namespace App\Http\Controllers\Admin\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModulesController extends Controller
{
    public function index($slug)
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

        if(!$tabs->contains('tab_slug', $tab_name)) {
            abort(404);
        }


        return view('admin.pages.modules.index', compact('module', 'tabs', 'tab_name'));
    }
}
