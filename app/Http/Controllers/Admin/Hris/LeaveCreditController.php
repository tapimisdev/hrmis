<?php

namespace App\Http\Controllers\Admin\Hris;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\EmployeeService;
use App\Services\GenerateService;
use App\Exports\LeaveCreditsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class LeaveCreditController extends Controller
{

    protected $employeeService;
    protected $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    

        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function index(Request $request, ? string $employee_no = null) {


        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);
        $leaves = $this->employeeService->getLeaveTypes($employee_no, [true, false]);
        $data = [];

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        if($leaves['status'] == 'eligible') {

            $leaveTypes = $leaves['data'];
            $monthYear = now()->format('Y-m'); 

            foreach($leaveTypes as $types) {

                $credits = $this->employeeService->getLeaveCredits($employee_no, $types->leave_id, false);
                $latestCredits = $this->employeeService->getLeaveCredits($employee_no, $types->leave_id, false);
                $leaveSettings = $this->employeeService->getLeaveSettings($types->leave_id);

                $currBal = $credits->filter(function($q) use ($monthYear) {
                    return ($q->as_of ?? '') === $monthYear;
                })->values()->pluck('balance')->first() ?? 0;
                

                $hasAssignedDeduct = is_null($leaveSettings->deduct_credit_id)
                    || $types->leave_id == $leaveSettings->deduct_credit_id;

                $hasDeduction = !is_null($leaveSettings->deduct_credit_id);

                $data[] = [
                    'leave' => $types,
                    'credits' => $credits,
                    'latestCredits' => $latestCredits,
                    'currentMonthBalance' => $currBal,
                    'hasAssignedDeduct' => $hasAssignedDeduct,
                    'hasDeduction'  => $hasDeduction
                ];    
            } 

            return view('admin.pages.hris.leave-credits', compact('employee_no', 'isExists', 'data'));

        }

        return view('admin.pages.hris.leave-credits', compact('employee_no', 'isExists', 'data'));

    }

    public function download(string $employee_no, $leave_id)
    {
        $filename = "leave_credits_{$employee_no}_{$leave_id}.xlsx";

        return Excel::download(
            new LeaveCreditsExport($employee_no, $leave_id),
            $filename
        );
    }

    public function fetch($employee_no, Request $request) {
        $leave_id = $request->leave_id;
        $monthYear = $request->as_of;
        $credits = $this->employeeService->getLeaveCreditsByMonthYear($employee_no, $leave_id, $monthYear);

        return response()->json([
            'status' => 'success',
            'data' => $credits ?? []
        ]);
    }

    public function save(string $employee_no, Request $request)
    {
        $payload = $request->all();
        $as_of = $payload['as_of'] ?? null;
        $leave_id = $payload['leave_id'] ?? null;

        if (!$leave_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave type is required.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // If action is delete
            if (($payload['action'] ?? '') === 'delete' && $as_of) {
                $this->deleteLeaveCreditAndRecalculate($employee_no, $leave_id, $as_of);

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Leave credits deleted successfully.',
                    'redirect' => route('hris.employee.leave-credits', ['employee_no' => $employee_no]),
                ]);
            }

            // Validate input
            $validator = Validator::make($payload, [
                'as_of' => [
                    'required',
                    'date_format:Y-m',
                    // function ($attribute, $value, $fail) {
                    //     if (Carbon::createFromFormat('Y-m', $value)->startOfMonth()->lt(now()->startOfMonth())) {
                    //         $fail('The :attribute must be the current month or a future month.');
                    //     }
                    // }
                ],
                'earned'    => 'nullable|numeric',
                'deduction' => 'nullable|numeric',
                'remarks'   => 'nullable|string',
                'leave_id'  => 'required|integer|exists:leaves,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Get current month credits for this leave type
            $credits = $this->employeeService->getLeaveCreditsByMonthYear($employee_no, $leave_id, $as_of);
            $previous_balance = (float) ($credits['previous_balance'] ?? 0);
            $earned = (float) ($request->earned ?? 0);
            $deduction = (float) ($request->deduction ?? 0);
            $balance = $previous_balance + $earned - $deduction;

            // Save or update current month credit for this leave type
            DB::table('leave_credits')->updateOrInsert(
                [
                    'employee_no' => $employee_no,
                    'leave_id'    => $leave_id,
                    'as_of'       => $as_of,
                ],
                [
                    'previous'   => $previous_balance,
                    'earned'     => $earned,
                    'deducted'   => $deduction,
                    'balance'    => $balance,
                    'remarks'    => $request->remarks,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Recalculate future credits for this leave type only
            $this->recalculateFutureCredits($employee_no, $leave_id, $as_of, $balance);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Leave credits saved successfully.',
                'redirect' => route('hris.employee.leave-credits', ['employee_no' => $employee_no]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a credit and recalculate future balances for a specific leave type
     */
    protected function deleteLeaveCreditAndRecalculate(string $employee_no, int $leave_id, string $as_of)
    {
        DB::table('leave_credits')
            ->where('employee_no', $employee_no)
            ->where('leave_id', $leave_id)
            ->where('as_of', $as_of)
            ->delete();

        $runningBalance = DB::table('leave_credits')
            ->where('employee_no', $employee_no)
            ->where('leave_id', $leave_id)
            ->where('as_of', '<', $as_of)
            ->orderByDesc('as_of')
            ->value('balance') ?? 0;

        $this->recalculateFutureCredits($employee_no, $leave_id, $as_of, $runningBalance);
    }

    /**
     * Recalculate balances for all future credits of a specific leave type
     */
    protected function recalculateFutureCredits(
        string $employee_no,
        int $leave_id,
        string $as_of,
        float $startingBalance
    ) {
        $futureCredits = DB::table('leave_credits')
            ->where('employee_no', $employee_no)
            ->where('leave_id', $leave_id)
            ->where('as_of', '>', $as_of)
            ->orderBy('as_of')
            ->get();

        $runningBalance = round($startingBalance, 2);

        foreach ($futureCredits as $credit) {
            $earned   = round($credit->earned, 2);
            $deducted = round($credit->deducted, 2);

            $newBalance = round($runningBalance + $earned - $deducted, 2);

            DB::table('leave_credits')
                ->where('id', $credit->id)
                ->update([
                    'previous'   => $runningBalance,
                    'balance'    => $newBalance,
                    'updated_at' => now(),
                ]);

            $runningBalance = $newBalance;
        }

        session()->flash('active_leave_id', $leave_id);
    }


}
