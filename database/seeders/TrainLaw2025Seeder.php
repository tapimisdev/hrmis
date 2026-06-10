<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainLaw2025Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Optional: set all existing to inactive (so 2025 becomes the only active one)
            DB::table('train_law')->update(['is_active' => false]);

            // Upsert TRAIN law year row (avoid duplicates if you re-run seeder)
            $year = '2025';

            $trainLawId = DB::table('train_law')->where('year', $year)->value('id');

            if (!$trainLawId) {
                $trainLawId = DB::table('train_law')->insertGetId([
                    'year'       => $year,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('train_law')->where('id', $trainLawId)->update([
                    'is_active'  => true,
                    'updated_at' => now(),
                ]);
            }

            // Clear existing items for that year (so re-run is clean)
            DB::table('train_law_items')->where('train_law_id', $trainLawId)->delete();

            // TRAIN graduated income tax table (effective 2023 onwards; applies in 2025)
            $items = [
                [
                    'income_from' => 0.00,
                    'income_to'   => 250000.00,
                    'fixed_tax'   => 0.00,
                    'tax_rate'    => 0.00,
                    'excess_over' => 0.00,
                ],
                [
                    'income_from' => 250000.01,
                    'income_to'   => 400000.00,
                    'fixed_tax'   => 0.00,
                    'tax_rate'    => 15.00,
                    'excess_over' => 250000.00,
                ],
                [
                    'income_from' => 400000.01,
                    'income_to'   => 800000.00,
                    'fixed_tax'   => 22500.00,
                    'tax_rate'    => 20.00,
                    'excess_over' => 400000.00,
                ],
                [
                    'income_from' => 800000.01,
                    'income_to'   => 2000000.00,
                    'fixed_tax'   => 102500.00,
                    'tax_rate'    => 25.00,
                    'excess_over' => 800000.00,
                ],
                [
                    'income_from' => 2000000.01,
                    'income_to'   => 8000000.00,
                    'fixed_tax'   => 402500.00,
                    'tax_rate'    => 30.00,
                    'excess_over' => 2000000.00,
                ],
                [
                    'income_from' => 8000000.01,
                    'income_to'   => null,       // last bracket (above limit)
                    'fixed_tax'   => 2202500.00,
                    'tax_rate'    => 35.00,
                    'excess_over' => 8000000.00,
                ],
            ];

            $now = now();

            DB::table('train_law_items')->insert(array_map(function ($r) use ($trainLawId, $now) {
                return array_merge($r, [
                    'train_law_id' => $trainLawId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }, $items));
        });
    }
}
