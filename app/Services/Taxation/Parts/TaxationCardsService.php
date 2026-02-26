<?php

namespace App\Services\Taxation\Parts;

use Illuminate\Database\ConnectionInterface;

class TaxationCardsService
{
    public function __construct(
        private readonly ConnectionInterface $db
    ) {}

    public function getTaxationEmployeesTotalCards(int $id): ?object
    {
        $taxation = $this->db->table('taxation_employees')
            ->where('taxation_id', $id)
            ->get();

        if ($taxation->isEmpty()) {
            return null;
        }

        $totalEmployees   = $taxation->count();
        $totalGross       = $taxation->sum('amount_gross');
        $totalTaxable     = $taxation->sum('amount_annual_taxable');
        $totalAnnualTax   = $taxation->sum('amount_annual_tax');

        return (object) [
            [
                'title' => 'Employees Included',
                'value' => number_format($totalEmployees),
                'icon'  => 'fa-solid fa-users',
            ],
            [
                'title' => 'Total Gross Income',
                'value' => '₱ ' . number_format($totalGross, 2),
                'icon'  => 'fa-solid fa-money-bill-trend-up',
            ],
            [
                'title' => 'Total Taxable Income',
                'value' => '₱ ' . number_format($totalTaxable, 2),
                'icon'  => 'fa-solid fa-receipt',
            ],
            [
                'title' => 'Total Annual Tax',
                'value' => '₱ ' . number_format($totalAnnualTax, 2),
                'icon'  => 'fa-solid fa-landmark',
            ],
        ];
    }
}
