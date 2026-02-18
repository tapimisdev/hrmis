<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelLeaveApiController extends Controller
{
    public function cancel(Request $request)
    {
        DB::beginTransaction();

        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        try {

            $data = DB::table('leave_applications as la')
                ->leftJoin('leave_dates as ld', 'la.id', '=', 'ld.leave_application_id')
                ->where('la.employee_no', $validated['employee_no'])
                ->where('ld.date', $validated['date'])
                ->where('la.status', 'approved')
                ->where('ld.isActive', true)
                ->select(
                    'ld.id as leave_date_id',
                    'ld.shift as shift',
                    'ld.credit_equivalent as date_credit_equivalent',
                    'la.employee_no as employee_no',
                    'la.credit_equivalent as credit_equivalent',
                    'la.id as leave_application_id',
                    'la.credit_remarks as credit_remarks',
                )
                ->first();

            if (is_null($data)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No approved leave found for this date.',
                ]);
            }

            $this->restoreCredits($data);

            $totalDates = DB::table('leave_dates')
                ->where('leave_application_id', $data->leave_application_id)
                ->where('isActive', true)
                ->count();

            DB::table('leave_dates')
                ->where('id', $data->leave_date_id)
                ->update([
                    'isActive' => false,
                ]);

            if ($totalDates == 1) {
                DB::table('leave_applications')
                    ->where('id', $data->leave_application_id)
                    ->update([
                        'status' => 'cancelled',
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $totalDates == 1
                    ? 'Leave cancelled completely.'
                    : 'Leave date cancelled successfully.',
                'remaining_active_dates' => $totalDates - 1,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error('Leave cancellation failed', [
                'employee_no' => $validated['employee_no'],
                'date' => $validated['date'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function restoreCredits($payload)
    {
        $employeeNo           = $payload->employee_no;
        $dateId               = $payload->leave_date_id;
        $shift                = $payload->shift;
        $applicationId        = $payload->leave_application_id;
        $dateCreditEquivalent = (float) ($payload->date_credit_equivalent ?? 0);

        // Get leave date
        $leaveDate = DB::table('leave_dates')
            ->where('leave_application_id', $applicationId)
            ->where('id', $dateId)
            ->first();

        if (!$leaveDate) {
            throw new \Exception('Leave data not found.');
        }

        // Determine shift suffix
        $suffix = match ($shift) {
            'morning'   => '(AM)',
            'afternoon' => '(PM)',
            'wholeday'  => '(WD)',
            default     => '',
        };

        $toRemove = strtoupper(Carbon::parse($leaveDate->date)->format('M j')) . ' ' . $suffix;

        // Fetch leave credit for employee & month
        $leaveCredit = DB::table('leave_credits')
            ->where('employee_no', $employeeNo)
            ->where('as_of', Carbon::parse($leaveDate->date)->format('Y-m'))
            ->latest('id')
            ->first();

        if (!$leaveCredit) {
            throw new \Exception('Leave credit record not found.');
        }

        $remarks = $leaveCredit->remarks ?? '';

        // Split remarks into multiple lines
        $lines = preg_split('/\r\n|\r|\n/', $remarks);

        $newLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Match dates and Eqv
            preg_match('/^(.*)\(Eqv:\s*([0-9.]+)\)$/', $line, $matches);

            if (!isset($matches[1])) {
                // Keep line as-is if it doesn't match pattern
                $newLines[] = $line;
                continue;
            }

            $datesPart  = trim($matches[1]);
            $currentEqv = (float) $matches[2];

            $datesArray = array_filter(array_map('trim', explode(',', $datesPart)));

            // Remove the target date if it exists in this line
            if (in_array($toRemove, array_map('strtoupper', $datesArray))) {
                $datesArray = array_filter($datesArray, fn($item) => strtoupper($item) !== strtoupper($toRemove));

                // Recalculate Eqv for this line
                $newEqv = 0;
                foreach ($datesArray as $item) {
                    if (str_contains($item, '(WD)')) $newEqv += 1;
                    elseif (str_contains($item, '(AM)') || str_contains($item, '(PM)')) $newEqv += 0.5;
                }

                // Add updated line if any dates remain
                if (!empty($datesArray)) {
                    $newLines[] = implode(', ', $datesArray) . ' (Eqv: ' . number_format($newEqv, 2) . ')';
                }
            } else {
                // Line does not contain the target date, keep it as-is
                $newLines[] = $line;
            }
        }

        $newRemarks = implode("\n", $newLines);

        $newDeducted = max(0, $leaveCredit->deducted - $dateCreditEquivalent);
        $newBalance  = $leaveCredit->balance + $dateCreditEquivalent;

        DB::table('leave_credits')
            ->where('id', $leaveCredit->id)
            ->update([
                'remarks'    => $newRemarks,
                'deducted'   => $newDeducted,
                'balance'    => $newBalance,
                'updated_at' => now(),
            ]);

        return true;
    }
}
