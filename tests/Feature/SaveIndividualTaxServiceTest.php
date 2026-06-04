<?php

namespace Tests\Feature;

use App\Services\Taxation\SaveIndividualTaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SaveIndividualTaxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_same_year_updates_employee_portion_instead_of_creating_duplicate_year_rows(): void
    {
        DB::table('train_law')->insert([
            'id' => 1,
            'year' => '2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SaveIndividualTaxService::class);

        $service->handle([
            'employee_nos' => ['EMP-001'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 80,
                    'hazard_pay' => 20,
                    'longevity' => 0,
                ],
                'employee_portions' => [
                    'EMP-001' => [
                        'salary' => 70,
                        'hazard_pay' => 20,
                        'longevity' => 10,
                    ],
                ],
            ],
        ]);

        $service->handle([
            'employee_nos' => ['EMP-001'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 80,
                    'hazard_pay' => 20,
                    'longevity' => 0,
                ],
                'employee_portions' => [
                    'EMP-001' => [
                        'salary' => 60,
                        'hazard_pay' => 25,
                        'longevity' => 15,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseCount('n_taxation', 1);
        $this->assertDatabaseCount('n_taxation_employees', 1);
        $this->assertDatabaseHas('n_taxation_employees', [
            'employee_no' => 'EMP-001',
            'salary' => 60,
            'hazard_pay' => 25,
            'longevity' => 15,
        ]);
    }

    public function test_saving_new_employee_for_same_year_keeps_existing_employee_rows(): void
    {
        DB::table('train_law')->insert([
            'id' => 1,
            'year' => '2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SaveIndividualTaxService::class);

        $service->handle([
            'employee_nos' => ['EMP-001'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 80,
                    'hazard_pay' => 20,
                    'longevity' => 0,
                ],
                'employee_portions' => [
                    'EMP-001' => [
                        'salary' => 80,
                        'hazard_pay' => 15,
                        'longevity' => 5,
                    ],
                ],
            ],
        ]);

        $service->handle([
            'employee_nos' => ['EMP-002'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 75,
                    'hazard_pay' => 20,
                    'longevity' => 5,
                ],
                'employee_portions' => [
                    'EMP-002' => [
                        'salary' => 65,
                        'hazard_pay' => 20,
                        'longevity' => 15,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseCount('n_taxation', 1);
        $this->assertDatabaseCount('n_taxation_employees', 2);
        $this->assertDatabaseHas('n_taxation_employees', [
            'employee_no' => 'EMP-001',
            'salary' => 80,
            'hazard_pay' => 15,
            'longevity' => 5,
        ]);
        $this->assertDatabaseHas('n_taxation_employees', [
            'employee_no' => 'EMP-002',
            'salary' => 65,
            'hazard_pay' => 20,
            'longevity' => 15,
        ]);
    }

    public function test_saving_tax_override_upserts_employee_tax_override_row(): void
    {
        DB::table('train_law')->insert([
            'id' => 1,
            'year' => '2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SaveIndividualTaxService::class);

        $payload = [
            'employee_nos' => ['EMP-001'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 80,
                    'hazard_pay' => 20,
                    'longevity' => 0,
                ],
                'employee_portions' => [
                    'EMP-001' => [
                        'salary' => 80,
                        'hazard_pay' => 20,
                        'longevity' => 0,
                    ],
                ],
                'tax_override' => [
                    'employee_no' => 'EMP-001',
                    'tax_type' => 'Salary Tax',
                    'month_number' => 12,
                    'amount' => 91.25,
                ],
            ],
        ];

        $service->handle($payload);

        $payload['n_taxation_settings']['tax_override']['amount'] = 95.50;
        $service->handle($payload);

        $this->assertDatabaseCount('n_taxation_employee_tax_overrides', 1);
        $this->assertDatabaseHas('n_taxation_employee_tax_overrides', [
            'employee_no' => 'EMP-001',
            'tax_type' => 'Salary Tax',
            'month_number' => 12,
            'amount' => 95.50,
        ]);
    }

    public function test_deleting_tax_override_removes_saved_override_row(): void
    {
        DB::table('train_law')->insert([
            'id' => 1,
            'year' => '2026',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SaveIndividualTaxService::class);

        $payload = [
            'employee_nos' => ['EMP-001'],
            'n_taxation' => ['Year' => 2026],
            'n_taxation_settings' => [
                'train_law_id' => 1,
                'bonuses' => [],
                'portion' => [
                    'salary' => 80,
                    'hazard_pay' => 20,
                    'longevity' => 0,
                ],
                'employee_portions' => [
                    'EMP-001' => [
                        'salary' => 80,
                        'hazard_pay' => 20,
                        'longevity' => 0,
                    ],
                ],
                'tax_override' => [
                    'employee_no' => 'EMP-001',
                    'tax_type' => 'Salary Tax',
                    'month_number' => 12,
                    'amount' => 91.25,
                ],
            ],
        ];

        $service->handle($payload);

        $payload['n_taxation_settings']['tax_override'] = [
            'employee_no' => 'EMP-001',
            'tax_type' => 'Salary Tax',
            'month_number' => 12,
            'action' => 'delete',
        ];

        $service->handle($payload);

        $this->assertDatabaseCount('n_taxation_employee_tax_overrides', 0);
    }
}
