<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bir2316 extends Model
{
    protected $table = 'bir_2316';

    protected $fillable = [
        'employee_id',
        'annual_tax_computation_id',
        'taxable_year',
        'employee_no',
        'employee_name',
        'employee_tin',
        'employee_address',
        'position',
        'employment_type',
        'employer_name',
        'employer_tin',
        'employer_address',
        'rdo_code',
        'annual_basic_salary',
        'hazard_pay',
        'longevity_pay',
        'government_bonuses',
        'de_minimis',
        'gross_compensation_income',
        'tax_exempt_bonus',
        'net_taxable_benefit',
        'gross_taxable_income',
        'allowable_deductions',
        'net_taxable_income',
        'annual_tax_due',
        'tax_withheld',
        'tax_refund_or_payable',
        'snapshot_data',
        'status',
        'generated_at',
        'locked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'generated_at' => 'datetime',
        'locked_at' => 'datetime',
    ];
}
