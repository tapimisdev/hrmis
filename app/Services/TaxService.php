<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TaxService {

    public function getAll($table, $year)
    {
        return DB::table($table)
                    ->where('year', $year)
                    ->get();
    }

}