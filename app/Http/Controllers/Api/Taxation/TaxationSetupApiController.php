<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Enums\TableSettingsEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxationSetupApiController extends Controller
{

    protected $hazard_tax_id, $salary_tax_id, $longevity_tax_id;

    public function __construct()
    {
        $payroll_components_settings = DB::table('payroll_components_settings')
            ->whereIn('type', [
                TableSettingsEnum::SALARY_ID->value,
                TableSettingsEnum::HAZARD_PA->value,
                TableSettingsEnum::LONGETIVITY->value,
            ])
            ->pluck('tax_id', 'type');

        $this->salary_tax_id    = $payroll_components_settings[TableSettingsEnum::SALARY_ID->value] ?? null;
        $this->hazard_tax_id    = $payroll_components_settings[TableSettingsEnum::HAZARD_PA->value] ?? null;
        $this->longevity_tax_id = $payroll_components_settings[TableSettingsEnum::LONGETIVITY->value] ?? null;
    }

    public function train_law_list()
    {
        $train_law = DB::table('train_law')
            ->where('is_active', true)
            ->get();

        return response()->json($train_law, 200);
    }

    public function hazard_tax_list() {

        $hazard_tax_list = $this->getList($this->hazard_tax_id);
        
        return response()->json($hazard_tax_list, 200);
    }

    public function salary_tax_list() {

        $salary_tax_list = $this->getList($this->salary_tax_id);
        
        return response()->json($salary_tax_list, 200);
    }

    public function longevity_tax_list() {

        $longevity_tax_list = $this->getList($this->longevity_tax_id);
        
        return response()->json($longevity_tax_list, 200);
    }

    private function getList($id)
    {
        return DB::table('payroll_components_years')
                ->select('id', 'year')
                ->where('payroll_component_id', $id)
                ->get();
    }
}
