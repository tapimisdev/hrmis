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
use App\Exports\OffsetCreditsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class OffsetCreditsController extends Controller
{

    public $employeeService;
    public $generateService;

    public function __construct(EmployeeService $employeeService, GenerateService $generateService)
    {
        $this->employeeService = $employeeService;
        $this->generateService = $generateService;    
        $this->middleware('permission:hr.hris.view')->only('leave_credits');
        $this->middleware('permission:hr.hris.edit')->only('save_credits');
    }

    public function index(Request $request, ? string $employee_no = null) {

        $isExists= $this->employeeService->checkIfEmployeeExists($employee_no);

        if(!is_null($employee_no) && !$isExists) {
            return redirect()->route('hris.employee.information');
        }

        $isEdit = false;
        $id = null;
        $currentMonth = Carbon::now()->format('Y-m');
        $credits = $this->employeeService->getOffsetCredits($employee_no, false);
        $latestCredits = $credits->first(function ($credit) use ($currentMonth) {
            return $credit->as_of === $currentMonth;
        });
        
        return view('admin.pages.hris.offset-credits', compact('isEdit', 'id', 'employee_no', 'isExists', 'credits', 'latestCredits'));
    }

    public function download(string $employee_no)
    {
        $filename = "offset_credits_{$employee_no}.xlsx";

        return Excel::download(
            new OffsetCreditsExport($employee_no),
            $filename
        );
    }

    public function fetch($employee_no, Request $request) {
        $monthYear = $request->as_of;
        $credits = $this->employeeService->getOffsetCreditsByMonthYear($employee_no, $monthYear);
        return response()->json([
            'status' => 'success',
            'data' => $credits ?? []
        ]);
    }

    public function save(string $employee_no, Request $request)
    {
        $payload = $request->all();
        $as_of = $payload['as_of'] ?? null;

        DB::beginTransaction();

        try {
            // If action is delete
            if (($payload['action'] ?? '') === 'delete' && $as_of) {
                $this->deleteOffsetCreditAndRecalculate($employee_no, $as_of);

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Offset credits deleted successfully.',
                    'redirect' => route('hris.employee.offset-credits', ['employee_no' => $employee_no]),
                ]);
            }

            // Validate input
            $validator = Validator::make($payload, [
                'as_of'     => 'required|date_format:Y-m',
                'earned'    => 'nullable|numeric',
                'deduction' => 'nullable|numeric',
                'remarks'   => 'nullable|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Get current month credits
            $credits = $this->employeeService->getOffsetCreditsByMonthYear($employee_no, $as_of);
            $previous_balance = round((float) ($credits['previous_balance'] ?? 0), 2);
            $earned = round((float) ($request->earned ?? 0), 2);
            $deduction = round((float) ($request->deduction ?? 0), 2);
            $balance = round($previous_balance + $earned - $deduction, 2);

            // Save or update current month
            DB::table('offset_credits')->updateOrInsert(
                [
                    'employee_no' => $employee_no,
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

            // Recalculate future credits
            $this->recalculateFutureCredits($employee_no, $as_of, $balance);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Offset credits saved successfully.',
                'redirect' => route('hris.employee.offset-credits', ['employee_no' => $employee_no]),
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
     * Delete a credit and recalculate future balances
     */
    protected function deleteOffsetCreditAndRecalculate(string $employee_no, string $as_of)
    {
        DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->where('as_of', $as_of)
            ->delete();

        $runningBalance = DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->where('as_of', '<', $as_of)
            ->orderByDesc('as_of')
            ->value('balance') ?? 0;

        $this->recalculateFutureCredits($employee_no, $as_of, $runningBalance);
    }

    /**
     * Recalculate balances for all future credits
     */
    protected function recalculateFutureCredits(
        string $employee_no,
        string $as_of,
        float $startingBalance
    ) {
        $futureCredits = DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->where('as_of', '>', $as_of)
            ->orderBy('as_of')
            ->get();

        $runningBalance = round($startingBalance, 2);

        foreach ($futureCredits as $credit) {
            $earned   = round($credit->earned, 2);
            $deducted = round($credit->deducted, 2);

            $newBalance = round($runningBalance + $earned - $deducted, 2);

            DB::table('offset_credits')
                ->where('id', $credit->id)
                ->update([
                    'previous'   => $runningBalance,
                    'balance'    => $newBalance,
                    'updated_at' => now(),
                ]);

            $runningBalance = $newBalance;
        }
    }



}
