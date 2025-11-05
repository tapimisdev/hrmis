<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Organization extends Controller
{
    public function division() {

        $data = DB::table('divisions')->get();

        return response()->json($data);
    }

    public function unit(int $division_id) {

        $data = DB::table('units')
            ->where('division_id', $division_id)
            ->get();

        return response()->json($data);
    }
}
