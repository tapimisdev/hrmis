<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoController extends Controller
{
    public function cdata(Request $request)
    {
        $rawInput = file_get_contents('php://input');

        $parts = explode("\t", trim($rawInput));

        $type = $parts[0] ?? null;
        $status = $parts[2] ?? null;

        Log::info('ADMS Data Received', [
            'raw_input' => $rawInput,
            'all' => $request->all(),
            'query' => array_merge($request->query(), [
                'Type' => $type,
                'Status' => $status,
            ]),
        ]);

        return response("OK", 200);
    }
}
