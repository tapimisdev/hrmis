<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'taxes' => 'Hazard Pay Tax',
                'icon'  => 'fa-solid fa-triangle-exclamation',
                'slug'  => 'hazard-pay-tax',
                'name'  => 'Hazard Pay Tax'
            ],
            [
                'taxes' => 'Longevity Tax',
                'icon'  => 'fa-solid fa-hourglass-half',
                'slug'  => 'longevity-tax',
                'name'  => 'Longevity Tax'
            ],
            [
                'taxes' => 'Salary Tax',
                'icon'  => 'fa-solid fa-money-bill',
                'slug'  => 'salary-tax',
                'name'  => 'Salary Tax'
            ],
        ];

        $modules = [
            [
                'module_name' => 'GSIS',
                'tab_name'    => 'contribution',
                'icon'        => 'fa-solid fa-building-columns',
                'slug'        => 'contribution',
                'order'       => 1,
            ],
            [
                'module_name' => 'PAG-IBIG',
                'tab_name'    => 'contribution',
                'icon'        => 'fa-solid fa-people-roof',
                'slug'        => 'contribution',
                'order'       => 2,
            ],
            [
                'module_name' => 'PHIL-HEALTH',
                'tab_name'    => 'contribution',
                'icon'        => 'fa-solid fa-heart-pulse',
                'slug'        => 'contribution',
                'order'       => 3,
            ],
            [
                'module_name' => 'Landbank',
                'tab_name'    => 'Base',
                'icon'        => 'fa-solid fa-landmark',
                'slug'        => 'base',
                'order'       => 4,
            ],
        ];


        foreach ($taxes as $tax) {
            DB::table('taxes')->updateOrInsert(
                ['name' => $tax['name']], 
                [                         
                    'icon'             => $tax['icon'],
                    'slug'             => $tax['slug'],
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }

        foreach ($modules as $m) {
            // Insert or update
            DB::table('modules')->updateOrInsert(
                ['module_name' => $m['module_name']], 
                [
                    'icon'       => $m['icon'],
                    'slug'       => $m['slug'],
                    'order'      => $m['order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Fetch the module (to get ID)
            $module = DB::table('modules')
                ->where('module_name', $m['module_name'])
                ->first();

            $slug = Str::slug($m['module_name']);

            // Insert first tab
            DB::table('module_tabs')->insert([
                'module_id'  => $module->id,
                'tab_name'   => $m['tab_name'],
                'tab_icon'   => 'fa-regular fa-file',
                'tab_slug'   => $slug,
                'order'      => $m['order'],
                'isActive'   => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
