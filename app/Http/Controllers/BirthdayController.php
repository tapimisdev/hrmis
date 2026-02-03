<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->has('birthday_shown')) {
            return response()->json([]);
        }

        $request->session()->put('birthday_shown', true);
        $request->session()->save();

        $today = Carbon::today();

        $employees = DB::table('employee_personal')
            ->select('employee_no', 'profile', 'firstname', 'lastname', 'middlename')
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->get()
            ->map(function ($row) {
                $profile = $row->profile
                    ? Storage::url('public/users/' . $row->employee_no . '/profile/' . $row->profile)
                    : 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
                        . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';

                return [
                    'profile' => $profile,
                    'name' => trim($row->firstname . ' ' . $row->middlename . ' ' . $row->lastname),
                ];
            });

        return response()->json($employees);
    }

}
