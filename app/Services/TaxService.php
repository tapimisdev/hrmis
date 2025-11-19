<?php

namespace App\Services;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Support\Facades\DB;

class TaxService {

    public function getAll($table, $parent_table_name, $parent_table_id)
    {
        $regular_id = EmploymentTypesEnum::REGULAR->value;

         $monthNames = [
            1 => 'january',
            2 => 'february',
            3 => 'march',
            4 => 'april',
            5 => 'may',
            6 => 'june',
            7 => 'july',
            8 => 'august',
            9 => 'september',
            10 => 'october',
            11 => 'november',
            12 => 'december',
        ];

        return  
        DB::table('employee_information as ei')
                ->leftJoin('employee_personal as ep' , 'ei.employee_no', '=', 'ep.employee_no')
                ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
                ->leftJoin('divisions as d', 'eo.division_id', '=', 'd.id')
                ->where('eo.employment_type_id', $regular_id)
                ->select(
                    'ei.employee_no',

                    'ep.suffix',
                    'ep.middlename',
                    'ep.lastname',
                    'ep.firstname',
                    'ep.firstname',

                    'd.code as division_code',
                    'd.name as division_name',
                )
                ->orderBy('ep.lastname', 'asc')
                ->get()
                ->map(function ($d) use ($table, $parent_table_name, $parent_table_id, $monthNames) {

                    $whereColumn = $parent_table_name . '_id';

                    for($month = 1; $month <= 12; $month++) {

                        $table_data = DB::table($table)
                            ->where('month', $month)
                            ->where($whereColumn, $parent_table_id)
                            ->where('employee_no', $d->employee_no)
                            ->first();

                        
                        $d->$whereColumn = $parent_table_id;
                        $monthName = $monthNames[$month];

                        $month_id = $monthName . '_id';
                        $d->$month_id = $table_data->id ?? null;

                        $d->$monthName = $table_data->amount ?? 0;
                    }
                    
                    return $d;
                });
    }

}