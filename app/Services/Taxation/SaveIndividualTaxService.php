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
            $taxation = NTaxation::query()->firstOrCreate([
                'Year' => (int) data_get($payload, 'n_taxation.Year'),
            ]);

            $setting = NTaxationSetting::query()->firstOrNew([
                'n_taxation_id' => $taxation->UniqueID,
            ]);

            $setting->fill([
                'train_law_id' => (int) data_get($payload, 'n_taxation_settings.train_law_id'),
                'is_longevity' => (bool) data_get($payload, 'n_taxation_settings.is_longevity'),
                'is_hazard_pay' => (bool) data_get($payload, 'n_taxation_settings.is_hazard_pay'),
                'is_less_bir' => (bool) data_get($payload, 'n_taxation_settings.is_less_bir'),
            ]);
            $setting->save();

            $setting->bonuses()->delete();
            $bonuses = collect(data_get($payload, 'n_taxation_settings.bonuses', []))
                ->map(fn (array $bonus) => [
                    'n_taxation_setting_id' => $setting->UniqueID,
                    'government_bonus_id' => (int) ($bonus['government_bonus_id'] ?? 0),
                ])
                ->filter(fn (array $bonus) => $bonus['government_bonus_id'] > 0)
                ->values();

            if ($bonuses->isNotEmpty()) {
                $setting->bonuses()->createMany($bonuses->all());
            }

            $setting->others()->delete();
            $others = collect(data_get($payload, 'n_taxation_settings.others', []))
                ->map(fn (array $other) => [
                    'n_taxation_setting_id' => $setting->UniqueID,
                    'name' => (string) ($other['name'] ?? ''),
                    'amount' => (float) ($other['amount'] ?? 0),
                    'is_taxable' => (bool) ($other['is_taxable'] ?? false),
                    'is_exempt_bir' => (bool) ($other['is_exempt_bir'] ?? false),
                ])
                ->values();

            if ($others->isNotEmpty()) {
                $setting->others()->createMany($others->all());
            }

            $setting->portion()->updateOrCreate(
                ['n_taxation_setting_id' => $setting->UniqueID],
                [
                    'hazard_pay' => (float) data_get($payload, 'n_taxation_settings.portion.hazard_pay', 0),
                    'salary' => (float) data_get($payload, 'n_taxation_settings.portion.salary', 0),
                    'longevity' => (float) data_get($payload, 'n_taxation_settings.portion.longevity', 0),
                ],
            );

            return [
                'message' => 'Individual tax settings saved successfully.',
                'data' => [
                    'employee_nos' => array_values(data_get($payload, 'employee_nos', [])),
                    'n_taxation_id' => $taxation->UniqueID,
                    'n_taxation_setting_id' => $setting->UniqueID,
                    'year' => $taxation->Year,
                ],
            ];
        });
    }
}
