<?php

namespace App\Services\Taxation\Parts;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

class TaxationSettingsService
{
    public function __construct(
        private readonly ConnectionInterface $db
    ) {}

    public function getActiveTaxationWithSettings(int $year): ?object
    {
        $taxation = $this->db->table('taxations')
            ->where('is_active', true)
            ->where('year', $year)
            ->first();

        if (!$taxation) {
            return null;
        }

        // ALWAYS initialize settings as object
        $taxation->settings = (object) [];

        $taxation->settings->other_earnings = $this->db->table('taxation_other_earnings')
            ->where('taxation_id', $taxation->id)
            ->get();

        $taxation->settings->other_allowables = $this->db->table('taxation_other_deductions')
            ->where('taxation_id', $taxation->id)
            ->get();

        // Mapping Components
        $mapping = [
            ["label" => "Basic Pay",      "type" => "earnings", "note" => "Taxable", "ok" => true],
            ["label" => "Mid-Year Bonus", "type" => "earnings", "note" => "Taxable", "ok" => (bool) $taxation->mid_year],
            ["label" => "Year-End Bonus", "type" => "earnings", "note" => "Taxable", "ok" => (bool) $taxation->year_end],
            ["label" => "Longevity Pay",  "type" => "earnings", "note" => "Taxable", "ok" => (bool) $taxation->longevity],
            ["label" => "Hazard Pay",     "type" => "earnings", "note" => "Taxable", "ok" => (bool) $taxation->hazard_pay],
        ];

        foreach ($taxation->settings->other_earnings as $earning) {
            $taxType = strtolower($earning->tax_type ?? 'taxable');

            $note = match ($taxType) {
                'non_taxable' => 'Non-Taxable',
                'exempt'      => 'Exempt',
                default       => 'Taxable',
            };

            $mapping[] = [
                "label" => Str::title((string) ($earning->name ?? '')),
                "type"  => "earnings",
                "note"  => $note,
                "ok"    => $taxType === 'taxable',
            ];
        }

        // Standard Allowables
        $mapping[] = ["label" => "- GSIS",       "type" => "allowables", "note" => "Allowable", "ok" => true];
        $mapping[] = ["label" => "- PhilHealth", "type" => "allowables", "note" => "Allowable", "ok" => true];
        $mapping[] = ["label" => "- Pag-IBIG",   "type" => "allowables", "note" => "Allowable", "ok" => true];

        foreach ($taxation->settings->other_allowables as $allowable) {
            $mapping[] = [
                "label" => Str::title((string) ($allowable->name ?? '')),
                "type"  => "allowables",
                "note"  => "Allowable",
                "ok"    => true,
            ];
        }

        $taxation->settings->mapping_components = $mapping;

        // Allocation Percentages
        $taxation->settings->allocation = [
            'basicPayPct'  => (float) $taxation->portion_basic_pay,
            'hazardPayPct' => (float) $taxation->portion_hazard_pay,
            'longevityPct' => (float) $taxation->portion_longevity_pay,
        ];

        // Train Law Info
        $trainLaw = $this->db->table('train_law')
            ->select('year')
            ->where('id', $taxation->train_law_id)
            ->first();

        $taxation->settings->train_law_year = $trainLaw->year ?? 'No Train Law';

        $taxation->settings->train_law_table = $this->db->table('train_law_items')
            ->select(
                'income_from',
                'income_to',
                'fixed_tax',
                'tax_rate as rate',
                'excess_over as excess'
            )
            ->where('train_law_id', $taxation->train_law_id)
            ->orderBy('income_from')
            ->get();

        return $taxation;
    }
}