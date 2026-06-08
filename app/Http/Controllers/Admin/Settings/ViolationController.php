<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\StoreViolationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class ViolationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr.violations.view')->only(['index', 'show']);
        $this->middleware('permission:hr.violations.create')->only(['create', 'store']);
        $this->middleware('permission:hr.violations.edit')->only(['edit', 'update']);
        $this->middleware('permission:hr.violations.delete')->only('destroy');
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = DB::table('violation_settings')
                ->where('is_active', true)
                ->orderBy('violation_type')
                ->get();

            return $this->datatable($query);
        }

        return view('admin.pages.settings.violations.index');
    }

    public function create()
    {
        return view('admin.pages.settings.violations.create');
    }

    public function store(StoreViolationRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            DB::table('violation_settings')->insert($this->payload($validated, [
                'is_active' => true,
                'created_at' => now(),
            ]));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Violation rule added successfully.',
                'redirect' => route('settings.violations.index'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $violation = DB::table('violation_settings')
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        abort_if(! $violation, 404, 'Violation rule not found.');

        return response()->json([
            'violation' => $violation,
        ]);
    }

    public function edit(string $id)
    {
        $violation = DB::table('violation_settings')->where('id', $id)->first();

        abort_if(! $violation, 404, 'Violation rule not found.');

        return view('admin.pages.settings.violations.edit', compact('violation'));
    }

    public function update(StoreViolationRequest $request, string $id)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $violation = DB::table('violation_settings')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            abort_if(! $violation, 404, 'Violation rule not found.');

            DB::table('violation_settings')->where('id', $id)->update($this->payload($validated));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Violation rule updated successfully.',
                'redirect' => route('settings.violations.index'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $violation = DB::table('violation_settings')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            abort_if(! $violation, 404, 'Violation rule not found.');

            DB::table('violation_settings')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Violation rule has been deleted.',
                'redirect' => '_self',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting this violation rule.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function payload(array $validated, array $extra = []): array
    {
        $payload = [
                'violation_type' => $this->canonicalViolationType($validated['violation_type']),
                'rule_trigger' => $validated['rule_trigger'],
                'evaluation_period' => $validated['evaluation_period'],
                'action_name' => $validated['action_name'],
                'threshold' => $validated['threshold'],
                'updated_at' => now(),
        ];

        $payload = array_merge($payload, $this->structuredRule($payload['violation_type']));
        $payload['monthly_threshold'] = $validated['monthly_threshold'];
        $payload['required_months'] = $this->requiredMonths((string) ($payload['period_type'] ?? 'monthly'), (int) $validated['threshold']);

        if (Schema::hasColumn('violation_settings', 'label')) {
            $payload['label'] = $payload['violation_type'];
        }

        return array_merge($this->filterColumns($payload), $extra);
    }

    private function filterColumns(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn('violation_settings', $column))
            ->all();
    }

    private function structuredRule(string $violationType): array
    {
        return match ($this->canonicalViolationType($violationType)) {
            'Tardiness / Late' => [
                'metric' => 'lates',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness' => [
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Tardiness - Consecutive' => [
                'metric' => 'lates',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Undertime' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Frequent Undertime' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Frequent Undertime - Consecutive' => [
                'metric' => 'undertimes',
                'monthly_threshold' => 10,
                'threshold_operator' => '>=',
                'required_months' => 2,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Unauthorized Absence' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'semester',
                'is_consecutive' => false,
            ],
            'Habitual Absenteeism - Consecutive' => [
                'metric' => 'unauthorized_absences',
                'monthly_threshold' => 2.5,
                'threshold_operator' => '>',
                'required_months' => 3,
                'period_type' => 'year',
                'is_consecutive' => true,
            ],
            'Discrepancy / Missing Timelog' => [
                'metric' => 'missing_timelogs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            'Missed Break Log' => [
                'metric' => 'missed_break_logs',
                'monthly_threshold' => 1,
                'threshold_operator' => '>=',
                'required_months' => 1,
                'period_type' => 'monthly',
                'is_consecutive' => false,
            ],
            default => [],
        };
    }

    private function requiredMonths(string $periodType, int $threshold): int
    {
        return in_array($periodType, ['semester', 'year'], true) ? $threshold : 1;
    }

    private function canonicalViolationType(string $violationType): string
    {
        $normalized = str($violationType)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->upper()
            ->toString();

        return match ($normalized) {
            'TARDINESS / LATE', 'TARDINESS/LATE', 'LATE', 'LATES' => 'Tardiness / Late',
            'HABITUAL TARDINESS' => 'Habitual Tardiness',
            'HABITUAL TARDINESS - CONSECUTIVE', 'HABITUAL TARDINESS-CONSECUTIVE' => 'Habitual Tardiness - Consecutive',
            'UNDERTIME' => 'Undertime',
            'FREQUENT UNDERTIME' => 'Frequent Undertime',
            'FREQUENT UNDERTIME - CONSECUTIVE', 'FREQUENT UNDERTIME-CONSECUTIVE' => 'Frequent Undertime - Consecutive',
            'UNAUTHORIZED ABSENCE', 'ABSENCE', 'ABSENCES' => 'Unauthorized Absence',
            'HABITUAL ABSENTEEISM' => 'Habitual Absenteeism',
            'HABITUAL ABSENTEEISM - CONSECUTIVE', 'HABITUAL ABSENTEEISM-CONSECUTIVE' => 'Habitual Absenteeism - Consecutive',
            'DISCREPANCY / MISSING TIMELOG', 'DISCREPANCY/MISSING TIMELOG', 'MISSING TIMELOG', 'MISSING TIMELOGS', 'INCOMPLETE TIMELOGS' => 'Discrepancy / Missing Timelog',
            'MISSED BREAK LOG', 'MISSED BREAK', 'MISSING BREAK LOG', 'MISSING BREAK' => 'Missed Break Log',
            default => $violationType,
        };
    }

    private function datatable($query)
    {
        return DataTables::of($query)
            ->addColumn('threshold', fn ($row) => $this->thresholdLabel($row))
            ->addColumn('actions', function ($row) {
                $deleteRoute = route('settings.violations.destroy', ['violation' => $row->id]);

                return '<div class="d-flex">' .
                    '<button data-id="' . $row->id . '" class="btn btn-primary btn ms-1 my-1 show-button" title="View">' .
                        '<i class="fas fa-eye"></i>' .
                    '</button>' .
                    '<a href="' . route('settings.violations.edit', $row->id) . '"
                        class="btn btn-secondary btn ms-1 my-1"
                        title="Edit">
                            <i class="fas fa-edit"></i>
                    </a>' .
                    '<button id="btn-delete" data-target="' . $deleteRoute . '" class="btn btn-danger btn ms-1 my-1" title="Delete">' .
                        '<i class="fas fa-trash-alt"></i>' .
                    '</button>' .
                '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    private function thresholdLabel(object $row): string
    {
        $monthlyThreshold = isset($row->monthly_threshold)
            ? rtrim(rtrim(number_format((float) $row->monthly_threshold, 2, '.', ''), '0'), '.')
            : $row->threshold;

        if (in_array($row->period_type ?? null, ['semester', 'year'], true)) {
            return "{$monthlyThreshold} per month / {$row->threshold} qualifying month(s)";
        }

        return match ($row->violation_type) {
            'Unauthorized Absence' => "{$monthlyThreshold} day(s)",
            default => "{$monthlyThreshold} occurrence(s)",
        };
    }
}
