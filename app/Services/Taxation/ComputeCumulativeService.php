<?php

namespace App\Services\Taxation;

use App\Jobs\Taxation\ForeCastEmployeeJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ComputeCumulativeService
{
    public function handle(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $taxationId = (int) data_get($payload, 'taxation_id');
            $type = (string) data_get($payload, 'type', 'forecast');

            $taxation = DB::table('taxations')
                ->where('id', $taxationId)
                ->where('is_active', true)
                ->first();

            if (!$taxation) {
                throw new RuntimeException('Active taxation record not found.');
            }

            $employeeNos = DB::table('taxation_employees')
                ->where('taxation_id', $taxationId)
                ->where('type', 'forecast')
                ->distinct()
                ->pluck('employee_no')
                ->filter()
                ->values()
                ->all();

            if (empty($employeeNos)) {
                throw new RuntimeException('No saved Q1 forecast employees found for this taxation record.');
            }

            $jobPayload = $this->buildPayload($taxation, $payload);

            DB::table('taxations')
                ->where('id', $taxationId)
                ->update([
                    'status' => 'processing',
                    'updated_at' => now(),
                ]);

            $jobs = collect($employeeNos)
                ->map(fn ($employeeNo) => new ForeCastEmployeeJob($taxationId, $employeeNo, $jobPayload))
                ->values()
                ->all();

            $batch = Bus::batch($jobs)
                ->name("Compute cumulative {$type} #{$taxationId}")
                ->then(function (Batch $batch) use ($taxationId) {
                    DB::table('taxations')
                        ->where('id', $taxationId)
                        ->update([
                            'status' => 'completed',
                            'updated_at' => now(),
                        ]);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($taxationId) {
                    DB::table('taxations')
                        ->where('id', $taxationId)
                        ->update([
                            'status' => 'failed',
                            'updated_at' => now(),
                        ]);
                })
                ->dispatch();

            DB::table('taxations')
                ->where('id', $taxationId)
                ->update([
                    'batch_id' => $batch->id,
                    'updated_at' => now(),
                ]);

            return [
                'batch_id' => $batch->id,
                'employee_count' => count($employeeNos),
                'type' => $type,
                'mode' => data_get($payload, 'mode'),
            ];
        });
    }

    private function buildPayload(object $taxation, array $payload): array
    {
        $basePayload = [
            'year' => (int) $taxation->year,
            'type' => (string) data_get($payload, 'type', 'forecast'),
            'hazardTaxId' => (int) $taxation->hazard_tax_id,
            'salaryTaxId' => (int) $taxation->salary_tax_id,
            'longevityTaxId' => (int) $taxation->longevity_id,
            'trainLawId' => (int) $taxation->train_law_id,
            'assumptions' => [
                'basicPay' => true,
                'midYear' => (bool) $taxation->mid_year,
                'yearEnd' => (bool) $taxation->year_end,
                'longevity' => (bool) $taxation->longevity,
                'hazardPay' => (bool) $taxation->hazard_pay,
                'lessBirRR32015' => (bool) $taxation->less_bir_rr3_2015,
            ],
            'deductions' => [
                'gsis' => (bool) $taxation->allowable_gsis,
                'philhealth' => (bool) $taxation->allowable_philhealth,
                'pagibig' => (bool) $taxation->allowable_pagibig,
            ],
            'othersEarnings' => DB::table('taxation_other_earnings')
                ->where('taxation_id', $taxation->id)
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'tax_type' => $item->tax_type,
                    'amount' => (float) $item->amount,
                ])
                ->values()
                ->all(),
            'othersDeductions' => DB::table('taxation_other_deductions')
                ->where('taxation_id', $taxation->id)
                ->get()
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                ])
                ->values()
                ->all(),
            'allocation' => [
                'hazardPayPct' => (float) $taxation->portion_hazard_pay,
                'basicPayPct' => (float) $taxation->portion_basic_pay,
                'longevityPct' => (float) $taxation->portion_longevity_pay,
            ],
        ];

        if (data_get($payload, 'mode') !== 'override') {
            return $basePayload;
        }

        $overrideAssumptions = (array) data_get($payload, 'assumptions', []);
        $basePayload['assumptions'] = [
            ...$basePayload['assumptions'],
            ...$overrideAssumptions,
            'basicPay' => true,
        ];

        $basePayload['othersEarnings'] = collect(data_get($payload, 'othersEarnings', []))
            ->filter(fn ($item) => filled(data_get($item, 'name')))
            ->map(fn ($item) => [
                'name' => trim((string) data_get($item, 'name', '')),
                'tax_type' => (string) data_get($item, 'tax_type', 'taxable'),
                'amount' => (float) data_get($item, 'amount', 0),
            ])
            ->values()
            ->all();

        $basePayload['othersDeductions'] = collect(data_get($payload, 'othersDeductions', []))
            ->filter(fn ($item) => filled(data_get($item, 'name')))
            ->map(fn ($item) => [
                'name' => trim((string) data_get($item, 'name', '')),
                'amount' => (float) data_get($item, 'amount', 0),
            ])
            ->values()
            ->all();

        return $basePayload;
    }
}
