<?php

namespace App\Http\Controllers\Admin\Channels;

use App\Http\Controllers\Controller;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Storage;

class OnlineUsersController extends Controller
{
    public function getProfile($user)
    {
        $roles = $user->getRoleNames();

        $isEmpRole = collect($roles)->contains(fn($role) => str_starts_with($role, 'emp_'));

        $service = app(EmployeeService::class);
        $employee_no = $service->getEmployeeNo($user->id);

        $information = $employee_no ? $service->getEmployee('information', $employee_no, null) : null;
        $personal = $employee_no ? $service->getEmployee('personal', $employee_no, null) : null;

        if ($personal && $personal->profile) {
            $profile = Storage::url('public/users/' . $employee_no . '/profile-image/' . $personal->profile);
        } else {
            $first = $personal->firstname ?? ($user->name ?? '?');
            $last = $personal->lastname ?? '';
            $profile = 'https://ui-avatars.com/api/?name='
                . urlencode($first . ' ' . $last)
                . '&background=random&color=fff&font-size=0.4&font-weight:bold&bold=true';
        }

        return [
            'id' => $user->id,
            'employee_no' => $employee_no ?? null,
            'name' => $user->name,
            'email' => $user->email,
            'profile' => $profile,
            'position' => $information->position_name ?? null,
            'isEmployee' => $isEmpRole
        ];
    }
}