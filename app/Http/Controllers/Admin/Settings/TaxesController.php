<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxesController extends Controller
{
    public function index()
    {
        $menu = [
            'salary_pay' => 'Salary Pay',
            'hazard_pay' => 'Hazard Pay',
            'longetivity_pay' => 'Longetivity Pay',
        ]; 

        $taxes = DB::table('taxes')->get();

        // Get existing tax settings
        $savedSettings = DB::table('tax_settings')
            ->pluck('tax_id', 'type')  // key = type, value = tax_id
            ->toArray();

        return view('admin.pages.settings.taxes.index', compact('menu', 'taxes', 'savedSettings'));
    }

    public function save(Request $request)
    {
        $payload = $request->all();

       $payload = array_filter($payload, function ($item) {
            return !empty($item['tax_id']); 
        });

        $errors = [];
        foreach ($payload as $item) {
            if (empty($item['data_id']) || !is_string($item['data_id'])) {
                $errors[$item['data_id'] ?? 'unknown'][] = 'Invalid menu key.';
            }
            if (empty($item['tax_id']) || !is_int($item['tax_id']) || !DB::table('taxes')->where('id', $item['tax_id'])->exists()) {
                $errors[$item['data_id'] ?? 'unknown'][] = 'Invalid tax selected.';
            }
        }

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {

            DB::beginTransaction();

            foreach ($payload as $value) {
                DB::table('tax_settings')->insert(
                    ['type' => $value['data_id']],
                    ['tax_id' => $value['tax_id']]
                );
            }

            DB::commit();

            // Return the updated saved settings
            $savedSettings = DB::table('tax_settings')
                ->pluck('tax_id', 'type')
                ->toArray();

            return response()->json([
                'status'  => 'success',
                'message' => 'Tax mappings saved successfully',
                'savedSettings' => $savedSettings
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
