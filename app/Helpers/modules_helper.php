<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!function_exists('getSetting')) {
    if (!function_exists('getSidebarModules')) {
        function getSidebarModules()
        {
            return Cache::remember('sidebar_modules_cache', 60 * 60, function () {
                return DB::table('modules as m')
                    ->leftJoin('module_tabs as mt', function($join) {
                        $join->on('m.id', '=', 'mt.module_id')
                            ->where('mt.isActive', true);
                    })
                    ->where('m.isActive', true)
                    ->select(
                        'm.*',
                        DB::raw('MIN(mt.tab_slug) as tab_slug')
                    )
                    ->groupBy('m.id')
                    ->orderBy('m.order')
                    ->get();
            });
        }
    }
}
