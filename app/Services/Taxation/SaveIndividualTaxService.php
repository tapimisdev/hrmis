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

            DB::table('n_taxation_employees')
                ->where('n_taxation_id', $taxation->id)
                ->delete();

            if ($employeeNos->isNotEmpty()) {
                DB::table('n_taxation_employees')->insert(
                    $employeeNos
                        ->map(fn (string $employeeNo) => [
                            'n_taxation_id' => $taxation->id,
                            'employee_no' => $employeeNo,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->all()
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
                    'hazard_pay' => (float) data_get($payload, 'n_taxation_settings.portion.hazard_pay', 0),
                    'salary' => (float) data_get($payload, 'n_taxation_settings.portion.salary', 0),
                    'longevity' => (float) data_get($payload, 'n_taxation_settings.portion.longevity', 0),
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
}
