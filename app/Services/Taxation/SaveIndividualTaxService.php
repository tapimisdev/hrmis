<?php

namespace App\Services\Taxation;

use App\Models\NTaxation;
use App\Models\NTaxationSetting;
use Illuminate\Support\Facades\DB;

class SaveIndividualTaxService
{
    public function handle(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $employeeNos = collect(data_get($payload, 'employee_nos', []))
                ->map(fn ($employeeNo) => trim((string) $employeeNo))
                ->filter()
                ->unique()
                ->values();
            $defaultPortion = $this->normalizePortion(
                (array) data_get($payload, 'n_taxation_settings.portion', [])
            );
            $employeePortions = $employeeNos
                ->mapWithKeys(function (string $employeeNo) use ($payload, $defaultPortion) {
                    return [$employeeNo => $this->normalizePortion(
                        array_merge(
                            $defaultPortion,
                            (array) data_get($payload, "n_taxation_settings.employee_portions.{$employeeNo}", [])
                        )
                    )];
                });

            $taxation = NTaxation::query()->firstOrCreate([
                'Year' => (int) data_get($payload, 'n_taxation.Year'),
            ]);

            $setting = NTaxationSetting::query()->firstOrNew([
                'n_taxation_id' => $taxation->id,
            ]);

            $setting->fill([
                'train_law_id' => (int) data_get($payload, 'n_taxation_settings.train_law_id'),
            ]);
            $setting->save();

            if ($employeeNos->isNotEmpty()) {
                DB::table('n_taxation_employees')->upsert(
                    $employeeNos
                        ->map(fn (string $employeeNo) => [
                            'n_taxation_id' => $taxation->id,
                            'employee_no' => $employeeNo,
                            'salary' => (float) data_get($employeePortions, "{$employeeNo}.salary", 0),
                            'hazard_pay' => (float) data_get($employeePortions, "{$employeeNo}.hazard_pay", 0),
                            'longevity' => (float) data_get($employeePortions, "{$employeeNo}.longevity", 0),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->all(),
                    ['n_taxation_id', 'employee_no'],
                    ['salary', 'hazard_pay', 'longevity', 'updated_at']
                );
            }

            $setting->bonuses()->delete();
            $bonuses = collect(data_get($payload, 'n_taxation_settings.bonuses', []))
                ->map(fn (array $bonus) => [
                    'n_taxation_setting_id' => $setting->id,
                    'government_bonus_id' => (int) ($bonus['government_bonus_id'] ?? 0),
                ])
                ->filter(fn (array $bonus) => $bonus['government_bonus_id'] > 0)
                ->values();

            if ($bonuses->isNotEmpty()) {
                $setting->bonuses()->createMany($bonuses->all());
            }

            $setting->portion()->updateOrCreate(
                ['n_taxation_setting_id' => $setting->id],
                [
                    'hazard_pay' => (float) ($defaultPortion['hazard_pay'] ?? 0),
                    'salary' => (float) ($defaultPortion['salary'] ?? 0),
                    'longevity' => (float) ($defaultPortion['longevity'] ?? 0),
                ],
            );

            return [
                'message' => 'Individual tax settings saved successfully.',
                'data' => [
                    'employee_nos' => $employeeNos->all(),
                    'n_taxation_id' => $taxation->id,
                    'n_taxation_setting_id' => $setting->id,
                    'year' => $taxation->Year,
                    'selected_taxation_settings' => [
                        'id' => $setting->id,
                        'train_law_id' => (int) $setting->train_law_id,
                    ],
                ],
            ];
        });
    }

    private function normalizePortion(array $portion): array
    {
        return [
            'salary' => (float) ($portion['salary'] ?? 80),
            'hazard_pay' => (float) ($portion['hazard_pay'] ?? 20),
            'longevity' => (float) ($portion['longevity'] ?? 0),
        ];
    }
}
