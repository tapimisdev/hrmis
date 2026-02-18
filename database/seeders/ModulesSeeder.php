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
        $components = [
            [
                'icon'  => 'fa-solid fa-parachute-box',
                'slug'  => 'personnel-economic-relief-allowance',
                'name'  => 'PERA',
                'type'  => 'earnings'
            ],
            [
                'icon'  => 'fa-solid fa-vest',
                'slug'  => 'representation-allowance',
                'name'  => 'Representation Allowance',
                'type'  => 'earnings'
            ],
            [
                'icon'  => 'fa-solid fa-car',
                'slug'  => 'transportation-allowance',
                'name'  => 'Transportation Allowance',
                'type'  => 'earnings'
            ],
            [
                'icon'  => 'fa-solid fa-helmet-safety',
                'slug'  => 'hazard-pay',
                'name'  => 'Hazard Pay',
                'type'  => 'earnings'
            ],
            [
                'icon'  => 'fa-solid fa-triangle-exclamation',
                'slug'  => 'hazard-pay-tax',
                'name'  => 'Hazard Pay Tax',
                'type'  => 'taxes'
            ],
            [
                'icon'  => 'fa-solid fa-timeline',
                'slug'  => 'longetivity-pay',
                'name'  => 'Longetivity Pay',
                'type'  => 'earnings'
            ],
            [
                'icon'  => 'fa-solid fa-hourglass-half',
                'slug'  => 'longetivity-tax',
                'name'  => 'Longetivity Tax',
                'type'  => 'taxes'
            ],
            [
                'icon'  => 'fa-solid fa-money-bill',
                'slug'  => 'salary-tax',
                'name'  => 'Salary Tax',
                'type'  => 'taxes'
            ],
            [
                'icon'  => 'fa-solid fa-money-bill',
                'slug'  => 'ewt-2%',
                'name'  => 'EWT (2%)',
                'type'  => 'taxes'
            ],
            [
                'icon'  => 'fa-solid fa-money-bill',
                'slug'  => 'percentage-tax-3%',
                'name'  => 'Percentage tax (3%)',
                'type'  => 'taxes'
            ],
            [
                'icon'  => 'fa-solid fa-money-bill',
                'slug'  => 'tax-ewt-5%',
                'name'  => 'Tax (ewt: 5%)',
                'type'  => 'taxes'
            ],
        ];

        $modules = [
            [
                'module_name' => 'GSIS',
                'tab_name'    => 'GSIS',
                'icon'        => 'fa-solid fa-building-columns',
                'slug'        => 'gsis',
                'order'       => 1,
            ],
            [
                'module_name' => 'PAG-IBIG',
                'tab_name'    => 'PAG-IBIG',
                'icon'        => 'fa-solid fa-people-roof',
                'slug'        => 'pag-ibig',
                'order'       => 2,
            ],
            [
                'module_name' => 'PHIL-HEALTH',
                'tab_name'    => 'PhilHealth',
                'icon'        => 'fa-solid fa-heart-pulse',
                'slug'        => 'philhealth',
                'order'       => 3,
            ],
            [
                'module_name' => 'Landbank',
                'tab_name'    => 'Landbank',
                'icon'        => 'fa-solid fa-landmark',
                'slug'        => 'landbank',
                'order'       => 4,
            ],
        ];

        foreach ($components as $component) {
            DB::table('payroll_components')->updateOrInsert(
                ['name' => $component['name']], 
                [                         
                    'icon'             => $component['icon'],
                    'slug'             => $component['slug'],
                    'type'             => $component['type'],
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

            $tab_slug = Str::slug($m['module_name']);

            // Insert first tab
            DB::table('module_tabs')->insert([
                'module_id'  => $module->id,
                'tab_name'   => $m['tab_name'],
                'tab_icon'   => 'fa-regular fa-file',
                'tab_slug'   => $tab_slug,
                'order'      => $m['order'],
                'isActive'   => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
