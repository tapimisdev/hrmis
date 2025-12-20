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
        // Check if birthday popup was already shown in this session
        if ($request->session()->get('birthday_shown', false)) {
            return response()->json([]); // empty array, do not show popup again
        }

        $today = Carbon::today();

        $employees = DB::table('employee_personal')
            ->select('employee_no', 'profile', 'firstname', 'lastname', 'middlename')
            ->whereDate('birthday', $today)
            ->get()
            ->map(function ($row) {
                $profile = $row->profile ?? null;

                if ($profile) {
                    $profile = Storage::url('uploads/employees/' . $row->employee_no . '/profile/' . $row->profile);
                } else {
                    $profile = 'https://ui-avatars.com/api/?name='
                        . urlencode(($row->firstname ?? '?') . ' ' . ($row->lastname ?? '?'))
                        . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
                }

                $name = $row->firstname . ' ' . $row->middlename . ' ' . $row->lastname;
                return [
                    'profile' => $profile,
                    'name' => $name,
                ];
            });

        // Mark as shown in session
        $request->session()->put('birthday_shown', true);

        return response()->json($employees);
    }
}
