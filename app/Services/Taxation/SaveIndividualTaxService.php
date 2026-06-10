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
            $syncEmployees = (bool) data_get($payload, 'n_taxation_settings.sync_employees', true);
            $taxOverride = $this->normalizeTaxOverride(
                (array) data_get($payload, 'n_taxation_settings.tax_override', [])
            );

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

            if ($syncEmployees) {
                $employeesToKeep = $employeeNos->all();

                DB::table('n_taxation_employees')
                    ->where('n_taxation_id', $taxation->id)
                    ->when(
                        !empty($employeesToKeep),
                        fn ($query) => $query->whereNotIn('employee_no', $employeesToKeep),
                        fn ($query) => $query
                    )
                    ->delete();

                DB::table('n_taxation_employee_tax_overrides')
                    ->where('n_taxation_id', $taxation->id)
                    ->when(
                        !empty($employeesToKeep),
                        fn ($query) => $query->whereNotIn('employee_no', $employeesToKeep),
                        fn ($query) => $query
                    )
                    ->delete();
            }

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

            if ($taxOverride !== null) {
                if (($taxOverride['action'] ?? 'upsert') === 'delete') {
                    DB::table('n_taxation_employee_tax_overrides')
                        ->where('n_taxation_id', $taxation->id)
                        ->where('employee_no', $taxOverride['employee_no'])
                        ->where('tax_type', $taxOverride['tax_type'])
                        ->where('month_number', $taxOverride['month_number'])
                        ->delete();
                } else {
                    DB::table('n_taxation_employee_tax_overrides')->upsert(
                        [[
                            'n_taxation_id' => $taxation->id,
                            'employee_no' => $taxOverride['employee_no'],
                            'tax_type' => $taxOverride['tax_type'],
                            'month_number' => $taxOverride['month_number'],
                            'amount' => $taxOverride['amount'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]],
                        ['n_taxation_id', 'employee_no', 'tax_type', 'month_number'],
                        ['amount', 'updated_at']
                    );
                }
            }

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

    private function normalizeTaxOverride(array $taxOverride): ?array
    {
        $employeeNo = trim((string) ($taxOverride['employee_no'] ?? ''));
        $taxType = trim((string) ($taxOverride['tax_type'] ?? ''));
        $monthNumber = (int) ($taxOverride['month_number'] ?? 0);

        if ($employeeNo === '' || $taxType === '' || $monthNumber < 1 || $monthNumber > 12) {
            return null;
        }

        return [
            'employee_no' => $employeeNo,
            'tax_type' => $taxType,
            'month_number' => $monthNumber,
            'amount' => (float) ($taxOverride['amount'] ?? 0),
            'action' => trim((string) ($taxOverride['action'] ?? 'upsert')) ?: 'upsert',
        ];
    }
}
