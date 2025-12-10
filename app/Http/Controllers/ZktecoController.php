<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoController extends Controller
{
    public function cdata(Request $request)
    {
        Log::info('ADMS Data Received', [
            'raw_input' => file_get_contents('php://input'),
            'all' => $request->all(),
            'query' => $request->query(),
        ]);

        return response("OK", 200);
    }
}
