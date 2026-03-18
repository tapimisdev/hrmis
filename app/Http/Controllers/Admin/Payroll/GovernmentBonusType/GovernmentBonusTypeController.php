<?php

namespace App\Http\Controllers\Admin\Payroll\GovernmentBonusType;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GovernmentBonusTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.government_bonus_rules.view')->only(['index']);
        $this->middleware('permission:hr.government_bonus_rules.create')->only(['store']);
        $this->middleware('permission:hr.government_bonus_rules.update')->only(['update']);
        $this->middleware('permission:hr.government_bonus_rules.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $data = DB::table('government_bonus_types as gbt')
                ->select('gbt.*')
                ->orderBy('gbt.name')
                ->get();

            return response()->json([
                'data' => $data,
            ]);
        }

        return view('admin.pages.payroll.government-bonus-types.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug')),
        ]);

        $validated = $this->validatePayload($request);

        DB::table('government_bonus_types')->insert([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Government bonus type created successfully.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug')),
        ]);

        $validated = $this->validatePayload($request, $id);

        DB::table('government_bonus_types')
            ->where('id', $id)
            ->update([
                ...$validated,
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Government bonus type updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        try {
            DB::table('government_bonus_types')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Government bonus type deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'This bonus type is already used in generated payrolls and cannot be deleted.',
            ], 409);
        }
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('government_bonus_types', 'name')->ignore($id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('government_bonus_types', 'slug')->ignore($id)],
            'computation_type' => ['required', Rule::in(['fixed', 'percentage', 'formula', 'manual'])],
            'computation_value' => ['nullable', 'numeric', 'min:0', 'required_if:computation_type,fixed,percentage'],
            'formula_expression' => ['nullable', 'string', 'required_if:computation_type,formula'],
            'computation_notes' => ['nullable', 'string'],
            'service_date_basis' => ['required', Rule::in(['organization', 'company'])],
            'min_years_of_service' => ['nullable', 'integer', 'min:0'],
            'require_active_account' => ['required', 'boolean'],
            'require_work_shift' => ['required', 'boolean'],
            'require_information' => ['required', 'boolean'],
            'require_salary' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
