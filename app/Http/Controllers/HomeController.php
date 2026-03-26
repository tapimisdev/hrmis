<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            return view('hris.home', [
                'features' => $this->features(),
            ]);
        }

        $user = Auth::user();
        $roles = $user->getRoleNames();

        if ($roles->contains(fn ($role) => str_starts_with($role, 'emp'))) {
            return redirect('employee/dashboard');
        }

        return redirect('admin/dashboard');
    }

    public function hrisHome()
    {
        return view('hris.home', [
            'features' => $this->features(),
        ]);
    }

    protected function features(): array
    {
        return [
            [
                'title' => 'Timekeeping',
                'description' => 'Capture daily logins, monitor attendance patterns, and keep work hours aligned with policy.',
            ],
            [
                'title' => 'Payroll',
                'description' => 'Process salary pay, hazard pay, longevity pay, and PERA RATA with fewer manual steps.',
            ],
            [
                'title' => 'Employee Records',
                'description' => 'Keep personal information, service records, and profile details organized in one place.',
            ],
            [
                'title' => 'Leave Requests',
                'description' => 'Handle leave filings, approvals, and status updates through a simple workflow.',
            ],
            [
                'title' => 'Announcements',
                'description' => 'Share office notices and system updates instantly across the platform.',
            ],
            [
                'title' => 'Messages',
                'description' => 'Send direct 1:1 messages with realtime delivery and read receipts.',
            ],
        ];
    }
}
