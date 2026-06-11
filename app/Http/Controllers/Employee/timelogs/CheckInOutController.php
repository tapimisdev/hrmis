<?php

namespace App\Http\Controllers\Employee\timelogs;

use App\Events\TimelogMonitoringUpdated;
use App\Enums\FnEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\Timelogs\CheckInOutRequest;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\TimelogsServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use TCPDF;

use function PHPUnit\Framework\isEmpty;

class CheckInOutController extends Controller
{
    protected $timelogsServices;
    protected $employeeService;

    public function __construct(
        TimelogsServices $timelogsServices, 
        EmployeeService $employeeService
    )
    {
        $this->timelogsServices = $timelogsServices;
        $this->employeeService = $employeeService;

        $this->middleware('permission:emp.timelogs.view')->only(['index']);
        $this->middleware('permission:emp.timelogs.checkin-out')->only(['store']);
    }

    public function index()
    {
        $user_id = auth()->user()->id;
    
        $employee_no = $this->employeeService->getEmployeeNo($user_id);
        $employee = $this->employeeService->getEmployee('information', $employee_no);
        $supervisor = $employee->units_supervisor ?? '';

        $is_allowed = $this->canUseWebTimeToday($employee_no)['allowed'];
        $isRequiredAR = $this->canUseWebTimeToday($employee_no)['isRequiredAR'];

        $query = $this->timelogsServices->getTimeLogs($user_id);

        if (request()->ajax()) {
            return $this->datatable($query);
        }

        return view('employee.pages.timelogs.checkin-out.index', compact(['employee_no', 'is_allowed', 'isRequiredAR', 'supervisor']));
    }

    public function create()
    {
        return view('employee.pages.timelogs.checkin-out.create');
    }

    public function todayLogs()
    {
        $logs = $this->timelogsServices->getTodaysLogs();

        return response()->json(['data' => $logs]);
    }

    public function store(CheckInOutRequest $request)
    {
        $validatedData = $request->validated();
        $fn = $validatedData['type'] ?? null;
        $accomplishment = $validatedData['accomplishment'] ?? null;

        $validatedData['user_id'] = auth()->user()->id;
        $validatedData['employee_no'] = auth()->user()->employee_no();

        $isAllowedToUseWebAccess = $this->canUseWebTimeToday($validatedData['employee_no']);

        if (!$isAllowedToUseWebAccess['allowed']) {
            throw new HttpException(
                403,
                'You are not permitted to use Web Time today. Kindly record your time by scanning your fingerprint on the biometric device.'
            );
        }

        $user = User::find($validatedData['user_id']);
        $user_schedule = $user->getShiftAndWorkSchedule();

        // Get current timelogs
        $current_timelog = $this->timelogsServices->getTodaysLogs($validatedData['user_id']);

        if (!empty($current_timelog['timeOut']) && (FnEnum::BreakOut->value == $fn || FnEnum::BreakIn->value == $fn)) {
            throw new HttpException(
                403,
                'You have already timed out for today. If you need to log a break in or break out, please request a timelog adjustment from your supervisor.'
            );
        }

        // Prevent duplicate logging for today
        if (
            !empty($current_timelog['timeIn']) &&
            !empty($current_timelog['breakOut']) &&
            !empty($current_timelog['breakIn']) &&
            !empty($current_timelog['timeOut']) &&
            !empty($current_timelog['overtimeIn']) &&
            !empty($current_timelog['overtimeOut'])
        ) {
            throw new HttpException(
                403,
                'You have already completed all your logs for today. No further action is needed.'
            );
        }

        // Use current date and time (Philippine timezone)
        $now = Carbon::now('Asia/Manila');
        $validatedData['date_time'] = $now;

        // Handle straight time-out
        if ($validatedData['type'] === 'timeOut') {
            $this->timelogsServices->straightToTimeOut($validatedData);
        }

        DB::beginTransaction();

        try {

            $timelog = DB::table('timelogs')->insertGetId([
                'user_id'           => $validatedData['user_id'],
                'employee_no'       => $validatedData['employee_no'],
                'date_time'         => $now,
                'fn'                => $fn,
                'shift_id'          => $user_schedule['shift_id'],
                'work_schedule_id'  => $user_schedule['work_schedule_id'],
                'created_at'        => now('Asia/Manila'),
                'updated_at'        => now('Asia/Manila'),
            ]);

            $time = $now->format('h:i:s A');

            if(!is_null($accomplishment)) {
                $this->generateDAR($validatedData['employee_no'], $timelog, $accomplishment);
            }

            DB::commit();

            event(new TimelogMonitoringUpdated(
                $now->toDateString(),
                (int) $validatedData['user_id'],
                (string) $fn,
            ));

            return response()->json([
                'message' => 'Your time log was recorded successfully.',
                'reason' => $isAllowedToUseWebAccess['reason'],
                'data'    => $timelog,
                'time'    => $time,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed to record time log entry.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function datatable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date', function ($row) {
                return \Carbon\Carbon::parse($row['date'])->format('F d, Y (l)') ?? '-- : -----';
            })
            ->addColumn('time_in', function ($row) {
                
                if($row['time_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['time_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('break_out', function ($row) {

                if($row['break_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['break_out'])->format('h:i:s A');
            })
            ->addColumn('break_in', function ($row) {
                
                if($row['break_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['break_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('time_out', function ($row) {
                
                if($row['time_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['time_out'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('overtime_in', function ($row) {
                
                if($row['overtime_in'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['overtime_in'])->format('h:i:s A') ?? '-- : -----';
            })
            ->addColumn('overtime_out', function ($row) {
                
                if($row['overtime_out'] == null) {
                    return '-- : -----';
                }

                return \Carbon\Carbon::parse($row['overtime_out'])->format('h:i:s A') ?? '-- : -----';
            })
            ->rawColumns(['date', 'time_in', 'break_out', 'break_in', 'time_out', 'overtime_in', 'overtime_out'])
            ->make(true);
    }

    public function canUseWebTimeToday(string $employeeNo): array
    {
        $now   = Carbon::now();
        $today = $now->toDateString(); // "2026-01-16"
        $dow   = $now->format('D');    // "Mon", "Tue", ...

        $rule = DB::table('web_time_access')
            ->where('employee_no', $employeeNo)
            ->where('effectivity_date', '<=', now())
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id') // tie-breaker
            ->first();


        if (!$rule) {
            return [
                'allowed' => false,
                'reason'  => 'No active Web Time access rule found.',
                'matched_rule_id' => null,
                'isRequiredAR' => false
            ];
        }

        if ((int) $rule->always === 1) {
            return [
                'allowed' => true,
                'reason'  => 'Allowed: always access.',
                'matched_rule_id' => $rule->id,
                'isRequiredAR' => $rule->isRequiredAccomplishment
            ];
        }


        // Decode JSON safely
        $specificDates = $rule->specific_dates ? json_decode($rule->specific_dates, true) : [];
        $daysOfWeek    = $rule->days_of_week ? json_decode($rule->days_of_week, true) : [];

        $specificDates = is_array($specificDates) ? $specificDates : [];
        $daysOfWeek    = is_array($daysOfWeek) ? $daysOfWeek : [];

        // 2) SPECIFIC DATES
        if (in_array($today, $specificDates, true)) {
            return [
                'allowed' => true,
                'reason'  => "Allowed: today's date ($today) is in specific_dates.",
                'matched_rule_id' => $rule->id,
                'isRequiredAR' => $rule->isRequiredAccomplishment
            ];
        }

        // 3) DAYS OF WEEK
        if (in_array($dow, $daysOfWeek, true)) {
            return [
                'allowed' => true,
                'reason'  => "Allowed: today ($dow) is in days_of_week.",
                'matched_rule_id' => $rule->id,
                'isRequiredAR' => $rule->isRequiredAccomplishment
            ];
        }

        return [
            'allowed' => false,
            'reason'  => 'Web Time is not allowed for you today based on your assigned schedule. Please use the biometric fingerprint scanner.',
            'matched_rule_id' => $rule->id,
            'isRequiredAR' => false
        ];
    }

    // private function generateDAR($employee_no, $timelog, $accomplishments)
    // {
    //     $employee = $this->employeeService->getEmployee('information', $employee_no);

    //     $now = Carbon::now()->format('F d, Y');
    //     $todayNumeric = Carbon::now()->format('Y-m-d');

    //     $fullname = $employee->firstname . ' ' . $employee->lastname;
    //     $division_name = $employee->division_name ?? 'N/A';
    //     $units_supervisor = $employee->units_supervisor ?? '';

    //     $templatePath = public_path('templates/daily-accomplishment-report.xlsx');
    //     $spreadsheet = IOFactory::load($templatePath);
    //     $sheet = $spreadsheet->getActiveSheet();

    //     // Header
    //     $sheet->setCellValue('B3', strtoupper($now));
    //     $sheet->setCellValue('B4', strtoupper($fullname));
    //     $sheet->setCellValue('B5', strtoupper($division_name));

    //     $startRow = 9;
    //     $currentRow = $startRow;
    //     $insertedRows = 0;
    //     $noCounter = 1;

    //     foreach ($accomplishments as $item) {

    //         $isEmpty = empty(trim($item['activity'] ?? ''))
    //             && empty(trim($item['details'] ?? ''))
    //             && empty(trim($item['remarks'] ?? ''))
    //             && empty(trim($item['mov'] ?? ''));

    //         if ($isEmpty) {
    //             continue;
    //         }

    //         if ($currentRow > $startRow) {
    //             $sheet->insertNewRowBefore($currentRow, 1);

    //             $prevRow = $currentRow - 1;

    //             $sheet->duplicateStyle(
    //                 $sheet->getStyle($prevRow),
    //                 $sheet->getStyle('A' . $currentRow . ':' . $sheet->getHighestColumn() . $currentRow)
    //             );

    //             $sheet->getRowDimension($currentRow)
    //                 ->setRowHeight($sheet->getRowDimension($prevRow)->getRowHeight());

    //             $insertedRows++;
    //         }

    //         $sheet->setCellValue('A' . $currentRow, $item['No.'] ?? $noCounter++);
    //         $sheet->setCellValue('B' . $currentRow, str_replace(["\r\n","\r","\n"], "\n", $item['activity'] ?? ''));
    //         $sheet->setCellValue('C' . $currentRow, str_replace(["\r\n","\r","\n"], "\n", $item['details'] ?? ''));
    //         $sheet->setCellValue('D' . $currentRow, str_replace(["\r\n","\r","\n"], "\n", $item['remarks'] ?? ''));
    //         $sheet->setCellValue('E' . $currentRow, str_replace(["\r\n","\r","\n"], "\n", $item['mov'] ?? ''));

    //         foreach (range('A','E') as $col) {
    //             $sheet->getStyle($col . $currentRow)
    //                 ->getAlignment()
    //                 ->setWrapText(true);
    //         }

    //         $highestColumn = $sheet->getHighestColumn();
    //         if (ord($highestColumn) > ord('E')) {
    //             $sheet->getStyle('F' . $currentRow . ':' . $highestColumn . $currentRow)
    //                 ->getBorders()
    //                 ->getAllBorders()
    //                 ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
    //         }

    //         $currentRow++;
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Division Supervisor Row Adjustment
    //     |--------------------------------------------------------------------------
    //     | Originally located at D13:E13
    //     | Move downward depending on inserted accomplishment rows
    //     */

    //     $supervisorRow = 13 + $insertedRows;

    //     $sheet->setCellValue('D' . $supervisorRow, strtoupper($units_supervisor));

    //     // Ensure merge still exists
    //     $sheet->mergeCells('D' . $supervisorRow . ':E' . $supervisorRow);

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Save File
    //     |--------------------------------------------------------------------------
    //     */

    //     $path = 'users/' . $employee_no . '/daily-accomplishment-reports/';
    //     $baseFilename = 'dar-' . $todayNumeric;
    //     $extension = '.xlsx';
    //     $filename = $baseFilename . $extension;

    //     $counter = 1;
    //     while (Storage::disk('public')->exists($path . $filename)) {
    //         $filename = $baseFilename . '-' . $counter . $extension;
    //         $counter++;
    //     }

    //     $fullPath = $path . $filename;

    //     $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    //     Storage::disk('public')->put($fullPath, '');
    //     $writer->save(storage_path('app/public/' . $fullPath));

    //     DB::table('accomplishment_reports')->insert([
    //         'timelog_id' => $timelog,
    //         'employee_no' => $employee_no,
    //         'file' => $fullPath,
    //         'created_at' => now(),
    //         'updated_at' => now()
    //     ]);
    // }

    private function generateDAR($employee_no, $timelog, $accomplishmentsHtml)
    {
        $employee = $this->employeeService->getEmployee('information', $employee_no);

        $fullname = strtoupper($employee->firstname . ' ' . $employee->lastname);
        $division_name = strtoupper($employee->division_name ?? 'N/A');
        $units_supervisor = strtoupper($employee->units_supervisor ?? '');
        $date = Carbon::now()->format('F d, Y');
        $todayNumeric = Carbon::now()->format('Y-m-d');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRIS');
        $pdf->SetAuthor($fullname);
        $pdf->SetTitle('Daily Accomplishment Report');

        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Daily Accomplishment Report (DAR)', 0, 1, 'C');
        $pdf->Ln(5);

        // Employee Information
        $pdf->SetFont('helvetica', '', 12);

        $pdf->Cell(35, 7, 'Date:', 0, 0);
        $pdf->Cell(0, 7, $date, 0, 1);

        $pdf->Cell(35, 7, 'Submitted by:', 0, 0);
        $pdf->Cell(0, 7, $fullname, 0, 1);

        $pdf->Cell(35, 7, 'Division:', 0, 0);
        $pdf->Cell(0, 7, $division_name, 0, 1);

        $pdf->Ln(5);

        // Horizontal line
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);

        // Accomplishments
        $pdf->SetFont('helvetica', '', 11);
        $pdf->writeHTML($accomplishmentsHtml, true, false, true, false, '');

        // // Set Y position 50mm from bottom
        // $pdf->SetY(150);
        // $pdf->SetFont('helvetica', '', 12);

        // // Right side position
        // $rightMargin = $pdf->getPageWidth() - $pdf->getMargins()['right'];
        // $blockWidth = 70; // width of signature block in mm

        // // Division Chief name (centered inside block)
        // $pdf->SetX($rightMargin - $blockWidth); // move cursor to the start of the block
        // $pdf->Cell($blockWidth, 5, $units_supervisor, 0, 1, 'C');

        // // Draw underline (same block width)
        // $yLine = $pdf->GetY() + 2;
        // $pdf->Line($rightMargin - $blockWidth, $yLine, $rightMargin, $yLine);

        // // Move cursor below line
        // $pdf->SetY($yLine + 5);
        // $pdf->SetX($rightMargin - $blockWidth);

        // // Title (centered inside block)
        // $pdf->Cell($blockWidth, 5, 'DIVISION CHIEF', 0, 1, 'C');

        // Save PDF
        $path = 'users/' . $employee_no . '/daily-accomplishment-reports/';
        $baseFilename = 'dar-' . $todayNumeric;
        $filename = $baseFilename . '.pdf';
        $counter = 1;

        while (Storage::disk('public')->exists($path . $filename)) {
            $filename = $baseFilename . '-' . $counter . '.pdf';
            $counter++;
        }

        $fullPath = $path . $filename;
        $pdfContents = $pdf->Output($filename, 'S');

        if (!Storage::disk('public')->put($fullPath, $pdfContents)) {
            throw new \RuntimeException('Unable to save the Daily Accomplishment Report.');
        }

        // Save to database
        DB::table('accomplishment_reports')->insert([
            'timelog_id' => $timelog,
            'employee_no' => $employee_no,
            'file' => $fullPath,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
