<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $data = [
            'FAD' => [
                [
                    'code' => 'MIS',
                    'name' => 'Management Information System Unit',
                    'description' => 'Provides information technology support, system development, and database management services for the Institute.',
                ],
                [
                    'code' => 'PRTY',
                    'name' => 'Property',
                    'description' => 'Manages and maintains the Institute’s properties, including inventory and asset records.',
                ],
                [
                    'code' => 'PROC',
                    'name' => 'Procurement',
                    'description' => 'Handles procurement of goods and services, ensuring compliance with policies and timely delivery.',
                ],
                [
                    'code' => 'HR',
                    'name' => 'HR Section',
                    'description' => 'Manages human resources functions including recruitment, employee relations, and personnel records.',
                ],
                [
                    'code' => 'CFAD',
                    'name' => 'Chief FAD',
                    'description' => 'Oversees all functions of the Finance and Administrative Division and ensures smooth operations.',
                ],
                [
                    'code' => 'CASH',
                    'name' => 'Cashier Section',
                    'description' => 'Responsible for collection, disbursement, and proper recording of cash transactions.',
                ],
                [
                    'code' => 'BUD',
                    'name' => 'Budget Section',
                    'description' => 'Prepares budget proposals, monitors expenditure, and ensures funds are allocated properly.',
                ],
                [
                    'code' => 'ACCT',
                    'name' => 'Accounting Section',
                    'description' => 'Maintains financial records, prepares financial reports, and ensures compliance with accounting standards.',
                ],
            ],

            'OD' => [
                [
                    'code' => 'OD',
                    'name' => 'Office of Director',
                    'description' => 'Oversees the overall operations, sets strategic direction, and ensures that all departments meet their objectives.',
                ],
                [
                    'code' => 'LU',
                    'name' => 'Legal Unit',
                    'description' => 'Provides legal advice, drafts contracts, ensures compliance with laws, and handles legal matters of the organization.',
                ],
            ],

            'TIPD' => [
                [
                    'code' => 'TIPD',
                    'name' => 'Technology Information and Promotion Division',
                    'description' => 'Handles IT infrastructure, manages information systems, and promotes technological innovations within the organization.',
                ]
            ],

            'IBOD' => [
                [
                    'code' => 'IBOD',
                    'name' => 'Investment and Business Operation Division',
                    'description' => 'Manages investments, business operations, and revenue-generating activities to support organizational growth.',
                ]
            ],

            'IDD' => [
                [
                    'code' => 'IDD',
                    'name' => 'Investment and Development Division',
                    'description' => 'Oversees development projects, evaluates investment opportunities, and ensures sustainable organizational expansion.',
                ]
            ],
        ];

        foreach ($data as $division => $units) {

            $divisionRecord = DB::table('divisions')->where('code', $division)->first();

            if ($divisionRecord) {
                $division_id = $divisionRecord->id;

                foreach ($units as $unit) {
                    DB::table('units')->updateOrInsert(
                        [
                            'code' => $unit['code'], 
                        ],
                        [
                            'name' => $unit['name'],
                            'division_id' => $division_id,
                        ]
                    );
                }
            }
        }
    }
}
