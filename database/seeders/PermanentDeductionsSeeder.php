<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermanentDeductionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $tabs = [
            // ================= GSIS =================
            [
                'module_id' => 1,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'GSIS Financial Assistance Loan',
                'tab_slug'  => 'gsis-financial-assistance-loan',
                'order'     => 2,
                'isActive'  => 1,
            ],
            [
                'module_id' => 1,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'GSIS MPL',
                'tab_slug'  => 'gsis-mpl',
                'order'     => 3,
                'isActive'  => 1,
            ],
            [
                'module_id' => 1,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'GSIS Policy Loan',
                'tab_slug'  => 'gsis-policy-loan',
                'order'     => 4,
                'isActive'  => 1,
            ],
            [
                'module_id' => 1,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'GSIS Emergency Loan',
                'tab_slug'  => 'gsis-emergency-loan',
                'order'     => 5,
                'isActive'  => 1,
            ],
            [
                'module_id' => 1,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'GSIS MPL LITE',
                'tab_slug'  => 'gsis-mpl-lite',
                'order'     => 6,
                'isActive'  => 1,
            ],

            // ================= PAG-IBIG =================
            [
                'module_id' => 2,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'Pag-Ibig MP 2 (Savings)',
                'tab_slug'  => 'pag-ibig-mp2-savings',
                'order'     => 2,
                'isActive'  => 1,
            ],
            [
                'module_id' => 2,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'Pag-Ibig Calamity Loan',
                'tab_slug'  => 'pag-ibig-calamity-loan',
                'order'     => 3,
                'isActive'  => 1,
            ],
            [
                'module_id' => 2,
                'tab_icon'  => 'fa-regular fa-file',
                'tab_name'  => 'Pag-Ibig MPL',
                'tab_slug'  => 'pag-ibig-mpl',
                'order'     => 4,
                'isActive'  => 1,
            ],
        ];

        foreach ($tabs as $tab) {
            DB::table('module_tabs')->updateOrInsert(
                [
                    // conditions to check (unique keys)
                    'module_id' => $tab['module_id'],
                    'tab_slug'  => $tab['tab_slug'],
                ],
                [
                    // values to insert/update
                    'tab_icon' => $tab['tab_icon'],
                    'tab_name' => $tab['tab_name'],
                    'order'    => $tab['order'],
                    'isActive' => $tab['isActive'],
                ]
            );
        }

    }
}
