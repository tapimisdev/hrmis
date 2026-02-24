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
                ->whereIn('la.status', ['approved', 'pending'])  
                ->where('ld.isActive', true)
                ->select(
                    'ld.id as leave_date_id',
                    'ld.shift as shift',
                    'ld.credit_equivalent as date_credit_equivalent',
                    'la.leave_id',
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
        $leave_id             = $payload->leave_id;
        $employeeNo           = $payload->employee_no;
        $dateId               = $payload->leave_date_id;
        $shift                = $payload->shift;
        $applicationId        = $payload->leave_application_id;
        $dateCreditEquivalent = (float) ($payload->date_credit_equivalent ?? 0);

        // 1. Resolve deduction leave_id
        $leaveSetting      = DB::table('leaves_settings')->where('leave_id', $leave_id)->first();
        $deductionLeaveId  = $leaveSetting->deduct_credit_id ?? null;
        $effectiveLeaveId  = $deductionLeaveId ?? $leave_id;

        // 2. Get leave date
        $leaveDate = DB::table('leave_dates')
            ->where('leave_application_id', $applicationId)
            ->where('id', $dateId)
            ->first();

        if (!$leaveDate) {
            throw new \Exception('Leave data not found.');
        }

        // 3. Build date string to remove from remarks
        $suffix   = match ($shift) {
            'morning'   => '(AM)',
            'afternoon' => '(PM)',
            'wholeday'  => '(WD)',
            default     => '',
        };
        $toRemove = strtoupper(Carbon::parse($leaveDate->date)->format('M j')) . ' ' . $suffix;
        $asOfMonth = Carbon::parse($leaveDate->date)->format('Y-m');

        // 4. Fetch leave credit record
        $leaveCredit = DB::table('leave_credits')
            ->where('leave_id', $effectiveLeaveId)
            ->where('employee_no', $employeeNo)
            ->where('as_of', $asOfMonth)
            ->latest('id')
            ->first();

        if (!$leaveCredit) {
            throw new \Exception('Leave credit record not found.');
        }

        // 5. Clean remarks safely
        $remarks = $leaveCredit->remarks ?? '';
        $lines   = preg_split('/\r\n|\r|\n/', $remarks);
        $newLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if ($leave_id !== $effectiveLeaveId) {
                // Handle special format: "(Eqv: …) [Optional Text]"
                preg_match('/^(.*)\(Eqv:\s*([0-9.]+)\)(.*)$/', $line, $matches);
                if (!isset($matches[1])) {
                    $newLines[] = $line;
                    continue;
                }

                $datesPart    = trim($matches[1]);
                $trailingText = $matches[3] ?? '';
                $datesArray   = array_filter(array_map('trim', explode(',', $datesPart)));

                if (in_array($toRemove, array_map('strtoupper', $datesArray))) {
                    $datesArray = array_filter(
                        $datesArray,
                        fn($item) => strtoupper($item) !== strtoupper($toRemove)
                    );

                    $newEqv = 0;
                    foreach ($datesArray as $item) {
                        if (str_contains($item, '(WD)')) {
                            $newEqv += 1;
                        } elseif (str_contains($item, '(AM)') || str_contains($item, '(PM)')) {
                            $newEqv += 0.5;
                        }
                    }

                    if (!empty($datesArray)) {
                        $newLines[] = implode(', ', $datesArray)
                            . ' (Eqv: ' . number_format($newEqv, 2) . ')'
                            . ' ' . trim($trailingText);
                    }

                } else {
                    $newLines[] = $line;
                }

            } else {
                // Standard format: "(Eqv: …)" only
                preg_match('/^(.*)\(Eqv:\s*([0-9.]+)\)$/', $line, $matches);
                if (!isset($matches[1])) {
                    $newLines[] = $line;
                    continue;
                }

                $datesPart  = trim($matches[1]);
                $datesArray = array_filter(array_map('trim', explode(',', $datesPart)));

                if (in_array($toRemove, array_map('strtoupper', $datesArray))) {
                    $datesArray = array_filter(
                        $datesArray,
                        fn($item) => strtoupper($item) !== strtoupper($toRemove)
                    );

                    $newEqv = 0;
                    foreach ($datesArray as $item) {
                        if (str_contains($item, '(WD)')) {
                            $newEqv += 1;
                        } elseif (str_contains($item, '(AM)') || str_contains($item, '(PM)')) {
                            $newEqv += 0.5;
                        }
                    }

                    if (!empty($datesArray)) {
                        $newLines[] = implode(', ', $datesArray)
                            . ' (Eqv: ' . number_format($newEqv, 2) . ')';
                    }

                } else {
                    $newLines[] = $line;
                }
            }
        }

        $newRemarks = implode("\n", $newLines);

        // 6. Restore deducted credits
        $newDeducted = max(0, (float)$leaveCredit->deducted - $dateCreditEquivalent);
        $newBalance  = (float)$leaveCredit->balance + $dateCreditEquivalent;

        DB::table('leave_credits')
            ->where('employee_no', $employeeNo)
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
