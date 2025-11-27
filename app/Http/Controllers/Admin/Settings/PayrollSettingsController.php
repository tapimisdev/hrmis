<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PayrollSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $components = DB::table('payroll_components')
            ->select('id', 'name', 'type')
            ->get();

        $earnings = $components->where('type', 'earnings')->values();
        $taxes    = $components->where('type', 'taxes')->values();

        $getLatestId = fn($type) => DB::table('payroll_components_settings')
            ->where('type', $type);
        
        $menu = [
            'salary_pay' => [
                'label'  => 'Salary Pay',
                'fields' => [
                    'tax_id' => [
                        'selected' => $getLatestId('salary_pay')->max('tax_id') ?? null,
                        'label'    => 'Tax Table',
                        'choices'  => $taxes,
                    ],
                ],
            ],
            'hazard_pay' => [
                'label'  => 'Hazard Pay',
                'fields' => [
                    'table_id' => [
                        'selected' => $getLatestId('hazard_pay')->max('table_id') ?? null,
                        'label'    => 'Hazard Table',
                        'choices'  => $earnings,
                    ],
                    'tax_id' => [
                        'selected' => $getLatestId('hazard_pay')->max('tax_id') ?? null,
                        'label'    => 'Tax Table',
                        'choices'  => $taxes,
                    ],
                ],
            ],
            'longetivity_pay' => [
                'label'  => 'Longevity Pay',
                'fields' => [
                    'table_id' => [
                        'selected' => $getLatestId('longetivity_pay')->max('table_id') ?? null,
                        'label'    => 'Longevity Table',
                        'choices'  => $earnings,
                    ],
                    'tax_id' => [
                        'selected' => $getLatestId('longetivity_pay')->max('tax_id') ?? null,
                        'label'    => 'Tax Table',
                        'choices'  => $taxes,
                    ],
                ],
            ],
        ];

        return view('admin.pages.payroll-settings.index', compact('menu'));
    }

    public function save(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();

        try {


            foreach ($data as $type => $fields) {
                $insertData = [];

                foreach ($fields as $fieldKey => $componentId) {
                    if (!empty($componentId)) {
                        $column = $fieldKey; 
                        $insertData[$column] = $componentId;
                    }
                }
                                
                if (!empty($insertData)) {
                    $insertData['type'] = $type;
                    $insertData['created_at'] = now();
                    $insertData['updated_at'] = now();

                    DB::table('payroll_components_settings')->insert($insertData);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payroll settings saved successfully.',
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }

    }

}
