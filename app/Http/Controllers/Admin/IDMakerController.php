<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IDMakerController extends Controller
{
    public function index() {

        $backgrounds = DB::table('id_card_background')
            ->orderBy('id', 'desc')
            ->get();

        $latestFront = $backgrounds->firstWhere('type', 'front');
        $latestBack  = $backgrounds->firstWhere('type', 'back');    

        return view('id-maker', compact('backgrounds', 'latestFront', 'latestBack'));
    }

    public function save_configuration(Request $request)
    {
        $response = [];

        // FRONT IMAGE 
        if ($request->hasFile('front_image')) {
            $frontPath = $request->file('front_image')
                ->store('id-templates', 'public');

            DB::table('id_card_background')->insert([
                'type' => 'front',
                'image' => $frontPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response['front_image'] = asset('storage/' . $frontPath);
        }

        // BACK IMAGE 
        if ($request->hasFile('back_image')) {
            $backPath = $request->file('back_image')
                ->store('id-templates', 'public');

            DB::table('id_card_background')->insert([
                'type' => 'back',
                'image' => $backPath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $response['back_image'] = asset('storage/' . $backPath);
        }

        return response()->json([
            'success' => true,
            ...$response
        ]);
    }

    public function save_generated() {

    }
}
