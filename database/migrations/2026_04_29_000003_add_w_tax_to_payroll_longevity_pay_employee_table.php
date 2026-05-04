<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_longevity_pay_employee', 'w_tax')) {
            Schema::table('payroll_longevity_pay_employee', function (Blueprint $table) {
                $table->decimal('w_tax', 12, 2)->default(0)->after('longevity_amount');
            });
        }

        $componentId = DB::table('payroll_components_settings')
            ->where('type', 'longetivity_pay')
            ->value('tax_id');

        if (!$componentId) {
            $componentId = DB::table('payroll_components')
                ->where('slug', 'longetivity-tax')
                ->value('id');
        }

        if (!$componentId) {
            return;
        }

        DB::table('payroll_longevity_pay_employee as plpe')
            ->join('payroll_longevity_pay as plp', 'plpe.payroll_longevity_pay_id', '=', 'plp.id')
            ->join('payroll_components_years as pcy', function ($join) use ($componentId) {
                $join->where('pcy.payroll_component_id', $componentId)
                    ->whereRaw('pcy.year = CAST(SUBSTRING(plp.month, 1, 4) AS UNSIGNED)');
            })
            ->join('employee_payroll_components as epc', function ($join) {
                $join->on('epc.tax_deduction_id', '=', 'pcy.id')
                    ->on('epc.employee_no', '=', 'plpe.employee_no')
                    ->whereRaw('epc.month = CAST(SUBSTRING(plp.month, 6, 2) AS UNSIGNED)');
            })
            ->update([
                'plpe.w_tax' => DB::raw('epc.amount'),
                'plpe.total' => DB::raw('plpe.longevity_amount - epc.amount'),
                'plpe.net_pay' => DB::raw('(plpe.longevity_amount - epc.amount) + plpe.adjustments'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_longevity_pay_employee', 'w_tax')) {
            Schema::table('payroll_longevity_pay_employee', function (Blueprint $table) {
                $table->dropColumn('w_tax');
            });
        }
    }
};
