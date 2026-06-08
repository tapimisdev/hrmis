<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainLawItemController extends Controller
{
    public function index($id)
    {
        $trainLaw = DB::table('train_law')->where('id', $id)->first();
        $trainLaw->items = DB::table('train_law_items')->where('train_law_id', $id)->get();

        return view('admin.pages.taxation.train-law.items.index', compact('trainLaw'));
    }

    public function store(Request $request, $trainLawId)
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],

            'rows.*.id' => ['nullable', 'integer'],

            'rows.*.income_from' => ['required', 'numeric', 'min:0'],
            'rows.*.income_to'   => ['nullable', 'numeric', 'min:0'],
            'rows.*.fixed_tax'   => ['required', 'numeric', 'min:0'],
            'rows.*.tax_rate'    => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.excess_over' => ['required', 'numeric', 'min:0'],
        ]);

        // Custom rule: income_to must be greater than income_from (when income_to is present)
        $errors = [];
        foreach ($validated['rows'] as $i => $row) {
            if (!is_null($row['income_to']) && $row['income_to'] <= $row['income_from']) {
                $errors["rows.$i.income_to"][] = "Income To must be greater than Income From.";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($validated, $trainLawId) {
            $incomingIds = collect($validated['rows'])->pluck('id')->filter()->values();

            // OPTIONAL: Delete records removed from UI
            DB::table('train_law_items')
                ->where('train_law_id', $trainLawId)
                ->when($incomingIds->count() > 0, fn($q) => $q->whereNotIn('id', $incomingIds))
                ->when($incomingIds->count() === 0, fn($q) => $q)
                ->delete();

            // Insert / Update each row
            foreach ($validated['rows'] as $row) {
                $data = [
                    'train_law_id' => $trainLawId,
                    'income_from'  => $row['income_from'],
                    'income_to'    => $row['income_to'],
                    'fixed_tax'    => $row['fixed_tax'],
                    'tax_rate'     => $row['tax_rate'],
                    'excess_over'  => $row['excess_over'],
                    'updated_at'   => now(),
                ];

                if (!empty($row['id'])) {
                    DB::table('train_law_items')
                        ->where('id', $row['id'])
                        ->where('train_law_id', $trainLawId)
                        ->update($data);
                } else {
                    $data['created_at'] = now();
                    DB::table('train_law_items')->insert($data);
                }
            }
        });

        $items = DB::table('train_law_items')
            ->where('train_law_id', $trainLawId)
            ->orderBy('income_from')
            ->get();

        return response()->json([
            'message' => 'Saved successfully.',
            'data' => $items,
        ]);
    }
}
