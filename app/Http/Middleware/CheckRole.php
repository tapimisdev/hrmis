<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Proceed only if authenticated and visiting employee routes
        if ($user && $request->is('employee*')) {
            $approvals = $this->checkApprovals($user->id);

            // Share approval data globally (for menus, etc.)
            view()->share('approvals', $approvals);

            // Detect which employee section is being accessed
            $currentType = $this->getCurrentApprovalType($request->path());

            // If route matches a specific type, check if user is allowed
            if ($currentType && isset($approvals[$currentType])) {
                if (!$approvals[$currentType]['allowed']) {
                    abort(403, 'You are not authorized to access this section.');
                }
            }
        }

        return $next($request);
    }

    /**
     * Determine the current approval type based on request path
     */
    private function getCurrentApprovalType(string $path): ?string
    {
        return match (true) {
            Str::contains($path, 'employee/approval-leaves') => 'leave',
            Str::contains($path, 'employee/approval-pass-slip'), Str::contains($path, 'pass_slip') => 'pass_slip',
            Str::contains($path, 'employee/approval-overtime') => 'overtime',
            Str::contains($path, 'employee/approval-payroll') => 'payroll',
            default => null,
        };
    }

    /**
     * Retrieve user approvals with their corresponding routes
     */
    private function checkApprovals(int $user_id): array
    {
        $types = [
            'leave'      => 'employee/approval-leaves',
            'pass_slip'  => 'employee/approval-pass-slip',
            'overtime'   => 'employee/approval-overtime',
            'payroll'    => 'employee/approval-payroll',
        ];

        // Join user approvers with approver type definitions
        $approverTypes = DB::table('application_approver_users as au')
            ->leftJoin('application_approver as a', 'a.id', '=', 'au.application_approver_id')
            ->where('au.user_id', $user_id)
            ->pluck('a.type')
            ->unique()
            ->toArray();

        // Check leave_approvals table separately
        $hasLeaveApproval = DB::table('leave_approvals')
            ->where('user_id', $user_id)
            ->exists();

        // Build access array
        $result = [];
        foreach ($types as $type => $route) {
            $hasAccess = in_array($type, $approverTypes) || ($type === 'leave' && $hasLeaveApproval);

            $result[$type] = [
                'allowed' => $hasAccess,
                'route'   => url($route),
            ];
        }

        return $result;
    }
}
