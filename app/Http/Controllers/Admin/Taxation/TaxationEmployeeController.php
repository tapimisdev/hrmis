<?php

namespace App\Http\Controllers\Admin\Taxation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxation\UpdateForecastRequest;
use App\Jobs\Taxation\ForeCastEmployeeJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxationEmployeeController extends Controller
{
    public function edit($id)
    {
        $emp = DB::table('taxation_employees as te')
            ->select(
                'te.allowable_gsis',
                'te.allowable_pagibig',
                'te.allowable_philhealth',
                'te.hazard_pay',
                'te.longevity',
                'te.mid_year',
                'te.year_end',
                'te.less_bir_rr3_2015',
                'te.portion_hazard_pay',
                'te.portion_basic_pay',
                'te.portion_longevity_pay'
            )
            ->where('te.id', $id)
            ->first();

        if (!$emp) {
            return response()->json([
                'message' => 'No data found.',
                'status' => 'edit fail',
            ], 404);
        }

        $other_earnings = DB::table('taxation_employee_other_earnings')
            ->where('taxation_employee_id', $id)
            ->where('is_default', false)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                    'tax_type' => $item->tax_type,
                ];
            })
            ->values();

        $other_deductions = DB::table('taxation_employee_other_deductions')
            ->where('taxation_employee_id', $id)
            ->where('is_default', false)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                ];
            })
            ->values();

        $data = [
            'assumptions' => [
                'basicPay' => true,
                'midYear' => (bool) $emp->mid_year,
                'yearEnd' => (bool) $emp->year_end,
                'longevity' => (bool) $emp->longevity,
                'hazardPay' => (bool) $emp->hazard_pay,
                'lessBirRR32015' => (bool) $emp->less_bir_rr3_2015,
            ],
            'deductions' => [
                'gsis' => (bool) $emp->allowable_gsis,
                'philhealth' => (bool) $emp->allowable_philhealth,
                'pagibig' => (bool) $emp->allowable_pagibig,
            ],
            'othersEarnings' => $other_earnings,
            'othersDeductions' => $other_deductions,

            'allocation' => [
                'basicPayPct' => $emp->portion_basic_pay,
                'hazardPayPct' => $emp->portion_hazard_pay,
                'longevityPct' => $emp->portion_longevity_pay,
            ]
        ];

        return response()->json($data);
    }

    public function update(UpdateForecastRequest $request, $id)
    {
        $payload = $request->validated();

        $taxation_employee = DB::table('taxation_employees as te')
            ->leftJoin('taxations as t', 'te.taxation_id', '=', 't.id')
            ->select(
                't.id',
                't.year',
                'te.employee_no',
                't.hazard_tax_id as hazardTaxId',
                't.salary_tax_id as salaryTaxId',
                't.longevity_id as longevityTaxId',
                't.train_law_id as trainLawId',
            )
            ->where('te.id', $id)
            ->first();

        if (!$taxation_employee) {
            return response()->json([
                'message' => 'Taxation employee not found.'
            ], 404);
        }

        $taxation_id = $taxation_employee->id;
        $payload['year'] = $taxation_employee->year;
        $payload['hazardTaxId'] = $taxation_employee->hazardTaxId;
        $payload['salaryTaxId'] = $taxation_employee->salaryTaxId;
        $payload['longevityTaxId'] = $taxation_employee->longevityTaxId;
        $payload['trainLawId'] = $taxation_employee->trainLawId;

        ForeCastEmployeeJob::dispatch(
            $taxation_id,
            $taxation_employee->employee_no,
            $payload
        );

        // delete old data
        DB::table('taxation_employees')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'message' => 'Forecast recomputation has been queued successfully.'
        ]);
    }

    public function breakdowns($taxation_employee_id)
    {

        $computations = DB::table('taxation_employee_computations')
            ->where('taxation_employee_id', $taxation_employee_id)
            ->get()
            ->keyBy('type') // This sets the key to the value of the 'type' column
            ->map(function ($item) {
                $item->raw_computation = json_decode($item->raw_computation, true);
                return $item;
            });

        return response()->json($computations);
    }
}
