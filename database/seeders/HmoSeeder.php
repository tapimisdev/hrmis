<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HmoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the module (to get ID)
        $module_id = DB::table('modules')->insertGetId([
                'module_name'   => 'HMO',
                'icon'   => 'fa-solid fa-kit-medical',
                'slug'   => 'hmo',
                'order'         => 5,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

        // Insert first tab
        DB::table('module_tabs')->insert([
            'module_id'  => $module_id,
            'tab_name'   => 'HMO',
            'tab_icon'   => 'fa-regular fa-file',
            'tab_slug'   => 'hmo',
            'order'      => 1,
            'isActive'   => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
