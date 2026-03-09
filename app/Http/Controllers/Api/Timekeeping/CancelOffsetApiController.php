<?php

namespace App\Http\Controllers\Api\Timekeeping;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelOffsetApiController extends Controller
{

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required',
            'date' => 'required|date',
        ]);

        return DB::transaction(function () use ($validated) {

            $data = DB::table('offset_applications as oa')
                ->leftJoin('offset_dates as od', 'oa.id', '=', 'od.offset_application_id')
                ->where('oa.employee_no', $validated['employee_no'])
                ->where('od.date', $validated['date'])
                ->whereIn('oa.status', ['approved', 'pending'])  
                ->where('od.isActive', true)
                ->select(
                    'od.id as offset_date_id',
                    'od.shift as shift',
                    'od.credit_equivalent as date_credit_equivalent',
                    'oa.employee_no as employee_no',
                    'oa.credit_equivalent as credit_equivalent',
                    'oa.id as offset_application_id',
                    'oa.credit_remarks as credit_remarks',
                )
                ->first();

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approved offset found for this date.',
                ]);
            }

            $this->restoreCredits($data);

            $totalDates = DB::table('offset_dates')
                ->where('offset_application_id', $data->offset_application_id)
                ->where('isActive', true)
                ->count();

            DB::table('offset_dates')
                ->where('id', $data->offset_date_id)
                ->update([
                    'isActive' => false,
                ]);

            //  If this was the last active date, cancel the entire application
            if ($totalDates == 1) {
                DB::table('offset_applications')
                    ->where('id', $data->offset_application_id)
                    ->update([
                        'status' => 'cancelled',
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => $totalDates == 1
                    ? 'Offset cancelled completely.'
                    : 'Offset date cancelled successfully.',
                'remaining_active_dates' => $totalDates - 1,
            ]);
        });
    }

    private function restoreCredits($payload)
    {
        $employeeNo           = $payload->employee_no;
        $dateId               = $payload->offset_date_id;
        $shift                = $payload->shift;
        $applicationId        = $payload->offset_application_id;
        $dateCreditEquivalent = (float) ($payload->date_credit_equivalent ?? 0);

        // Get offset date
        $offsetDate = DB::table('offset_dates')
            ->where('offset_application_id', $applicationId)
            ->where('id', $dateId)
            ->first();

        if (!$offsetDate) {
            throw new \Exception('Offset data not found.');
        }

        // Determine shift suffix
        $suffix = match ($shift) {
            'morning'   => '(AM)',
            'afternoon' => '(PM)',
            'wholeday'  => '(WD)',
            default     => '',
        };

        $toRemove = strtoupper(Carbon::parse($offsetDate->date)->format('M j')) . ' ' . $suffix;

        // Fetch offset credit for employee & month
        $offsetCredit = DB::table('offset_credits')
            ->where('employee_no', $employeeNo)
            ->where('as_of', Carbon::parse($offsetDate->date)->format('Y-m'))
            ->latest('id')
            ->first();

        if (!$offsetCredit) {
            throw new \Exception('Offset credit record not found.');
        }

        $remarks = $offsetCredit->remarks ?? '';

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

        $newDeducted = max(0, $offsetCredit->deducted - $dateCreditEquivalent);
        $newBalance  = $offsetCredit->balance + $dateCreditEquivalent;

        DB::table('offset_credits')
            ->where('id', $offsetCredit->id)
            ->update([
                'remarks'    => $newRemarks,
                'deducted'   => $newDeducted,
                'balance'    => $newBalance,
                'updated_at' => now(),
            ]);

        return true;
    }


}
