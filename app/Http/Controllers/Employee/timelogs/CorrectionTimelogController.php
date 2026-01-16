<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CorrectionRequest;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\returnArgument;

class CorrectionTimelogController extends Controller
{

    protected $timelog_service;
    protected $employee_service;

    protected $user_id;

    public function __construct(TimelogsServices $timelog_service, EmployeeService $employee_service)
    {
        $this->timelog_service = $timelog_service;
        $this->employee_service = $employee_service;

        $this->middleware('permission:hr.correction.view')->only('index');
        $this->middleware('permission:hr.correction.approval')->only(['edit', 'store']);

    }

    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer',
        ]);

        $corrections = DB::table('timelog_corrections')
            ->whereMonth('date', $request->input('month'))
            ->whereYear('date', $request->input('year'))
            ->get()
            ->map(function ($row) {
                $row->attachment = Storage::url($row->attachment);

                return $row;
            });

        return response()->json([
            'data' => $corrections
        ]);
    }

    public function edit(Request $request) {
        
        // Validate user_id and date
        $validated = $request->validate([
            'date'    => ['required', 'date'],
        ]);

        // Access validated data
        $employee_no = auth()->user()->employee_no();

        $user_id = DB::table('employee_information')->where('employee_no', $employee_no)->value('user_id');

        $date = Carbon::parse($validated['date']);

        $logs = $this->timelog_service->getTimeLogsWithPeriod(
            $user_id,
            $date->copy()->startOfDay(),  // start_date
            $date->copy()->endOfDay()     // end_date
        );

        return response(['data' => $logs, 'status' => 'success'], 200);
    }

    public function store(CorrectionRequest $request) 
    {
        $validatedData = $request->validated();

        $employee_no = auth()->user()->employee_no(); 

        DB::beginTransaction();

        try {
            $date = Carbon::parse($validatedData['date'])->format('Y-m-d');

            // Helper function to combine date + time
            $combineDateTime = function($date, $time) {
                return $time ? Carbon::parse("$date $time")->format('Y-m-d H:i:s') : null;
            };

            // Handle attachment if exists
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('corrections', 'public');
            }

            $schedule_and_Schift = DB::table('employee_shift_work_schedule')
                ->where('employee_no', $employee_no)
                ->latest('effectivity_date')
                ->first();

            $data = DB::table('timelog_corrections')->insert([
                'reference_no'      => $this->generate_reference_no(),
                'employee_no'       => $employee_no,
                'date'              => $validatedData['date'],
                'time_in'           => $combineDateTime($date, $validatedData['time_in']),
                'break_out'         => $combineDateTime($date, $validatedData['break_out']),
                'break_in'          => $combineDateTime($date, $validatedData['break_in']),
                'time_out'          => $combineDateTime($date, $validatedData['time_out']),
                'overtime_in'       => $combineDateTime($date, $validatedData['overtime_in']),
                'overtime_out'      => $combineDateTime($date, $validatedData['overtime_out']),
                'shift_id'          => $schedule_and_Schift->shift_id ?? null,
                'work_schedule_id'  => $schedule_and_Schift->work_schedule_id,
                'attachment'        => $attachmentPath,
                'remarks'           => $validatedData['remarks'] ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'message' => 'Correction Requested!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred: ' . $e->getMessage()
            ]);
        }    
    }

    private function generate_reference_no(): string
    {
        do {
            $date = now()->format('Ymd');
            $sequence = DB::table('timelog_corrections')
                ->whereDate('created_at', now()->toDateString())
                ->count() + 1;
            $ref = 'TCR-' . $date . '-' . str_pad($sequence, 2, '0', STR_PAD_LEFT);
        } while (
            DB::table('timelog_corrections')
                ->where('reference_no', $ref)
                ->exists()
        );

        return $ref;
    }
}
