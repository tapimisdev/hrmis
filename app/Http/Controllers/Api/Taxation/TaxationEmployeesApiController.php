<?php

namespace App\Http\Controllers\Api\Taxation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxationEmployeesApiController extends Controller
{
    public function breakdowns($taxation_employee_id)
    {
        $breakdowns = DB::table('taxation_employee_computations')
                        ->where('taxation_employee_id', $taxation_employee_id)
                        ->get();

        // $
    }


}
