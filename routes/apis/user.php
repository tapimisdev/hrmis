<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Route::get('/users', function () {
    $users = DB::table('users')
        ->leftJoin('employee_information as ei', 'users.id', '=', 'ei.user_id')
        ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
        ->select(
            'users.id',
            'users.name',
            'users.email',
            'ei.employee_no',
            'ep.profile',
            'ep.firstname',
            'ep.lastname'
        )
        ->orderBy('users.name')
        ->get()
        ->map(function ($user) {
            if ($user->profile) {
                $profile = Storage::url(
                    'public/users/' . $user->employee_no . '/profile-image/' . $user->profile,
                );
            } else {
                $fullName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
                $profile = 'https://ui-avatars.com/api/?name='
                    . urlencode($fullName !== '' ? $fullName : ($user->name ?? 'User'))
                    . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
            }

            return [
                'id' => $user->id,
                'employee_no' => $user->employee_no,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $profile,
                'position' => null,
            ];
        })
        ->values();

    return response()->json($users);
});
