<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ChangePasswordController extends Controller
{
    public function change(Request $request)
    {
        $user = $request->user();
        $user->load('employeeInformation');
        $employee_no = $user->employeeInformation->employee_no;

        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        if($request->isForcedUpdate) {
            $this->isPasswordChanged($employee_no);
        }

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function isPasswordChanged(string $employee_no) {

        DB::table('employee_information')->where('employee_no', $employee_no)
            ->update(
                [
                    'toUpdatePassword' => false,
                ]
            );

    }
}
