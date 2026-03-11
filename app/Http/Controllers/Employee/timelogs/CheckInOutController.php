<?php

namespace App\Http\Controllers\Employee\timelogs;

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
        $supervisor = $employee->division_supervisor ?? '';

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
        $rawAR = $validatedData['accomplishment'] ?? null;
        $accomplishment = $this->cleanAR($rawAR);

        $validatedData['user_id'] = auth()->user()->id;
        $validatedData['employee_no'] = auth()->user()->employee_no();

        // $isAllowedToUseWebAccess = $this->canUseWebTimeToday($validatedData['employee_no'])['allowed'];
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
            $this->generateDAR($validatedData['employee_no'], $timelog, $accomplishment);

            DB::commit();

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

    private function cleanAR($content)
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        // Get all rows
        $rows = $dom->getElementsByTagName('tr');

        $cleanData = [];
        $headers = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = $row->getElementsByTagName('td');

            if ($cells->length === 0) continue;

            $rowData = [];

            if ($rowIndex === 0) {
                foreach ($cells as $cell) {
                    $text = html_entity_decode($cell->textContent);
                    $text = str_replace("\xC2\xA0", '', $text); // remove non-breaking spaces
                    $text = preg_replace('/[ \t]+$/m', '', $text); // trim trailing spaces but keep newlines
                    $headers[] = trim($text); // headers can stay trimmed
                }
                continue;
            }

            foreach ($cells as $cellIndex => $cell) {
                $text = html_entity_decode($cell->textContent);
                $text = str_replace("\xC2\xA0", '', $text); // remove non-breaking spaces
                $text = preg_replace('/[ \t]+$/m', '', $text); // trim trailing spaces but keep newlines
                $text = str_replace(["\r\n", "\r"], "\n", $text); // normalize newlines

                $key = $headers[$cellIndex] ?? 'column_' . $cellIndex;
                $rowData[$key] = $text;
            }

            $cleanData[] = $rowData;
        }

        return $cleanData;
    }

    private function generateDAR($employee_no, $timelog, $accomplishments) {
        $employee = $this->employeeService->getEmployee('information', $employee_no);
        $now = Carbon::now()->format('F d, Y');
        $todayNumeric = Carbon::now()->format('Y-m-d'); // for filename
        $fullname = $employee->firstname . ' ' . $employee->lastname;
        $division_name = $employee->division_name ?? 'N/A';

        // Load template
        $templatePath = public_path('templates/daily-accomplishment-report.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Fill header info
        $sheet->setCellValue('B3', strtoupper($now));
        $sheet->setCellValue('B4', strtoupper($fullname));
        $sheet->setCellValue('B5', strtoupper($division_name));

        $startRow = 9;
        $noCounter = 1; 

        foreach ($accomplishments as $index => $item) {
            $isEmpty = empty(trim($item['Accomplishment / Activity'] ?? ''))
                    && empty(trim($item['Details'] ?? ''))
                    && empty(trim($item['Status / Remarks'] ?? ''))
                    && empty(trim($item['MOV (Means of Verification)'] ?? ''));

            if ($isEmpty) {
                continue;
            }

            $row = $startRow + $index;

            if ($index === 0) {
                $templateRowStyle = $sheet->getStyle($row);
                $templateRowHeight = $sheet->getRowDimension($row)->getRowHeight();
            } else {
                $prevRow = $row - 1;
                $sheet->insertNewRowBefore($row, 1);
                $sheet->duplicateStyle($sheet->getStyle($prevRow), $row);
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension($prevRow)->getRowHeight());
            }

            $sheet->setCellValue('A' . $row, $item['No.'] ?? $noCounter++);
            $sheet->setCellValue('B' . $row, str_replace(["\r\n","\r","\n"], "\n", $item['Accomplishment / Activity'] ?? ''));
            $sheet->setCellValue('C' . $row, str_replace(["\r\n","\r","\n"], "\n", $item['Details'] ?? ''));
            $sheet->setCellValue('D' . $row, str_replace(["\r\n","\r","\n"], "\n", $item['Status / Remarks'] ?? ''));
            $sheet->setCellValue('E' . $row, str_replace(["\r\n","\r","\n"], "\n", $item['MOV (Means of Verification)'] ?? ''));

            // Enable wrap text for these cells so new lines are visible
            foreach (range('A','E') as $col) {
                $sheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
            }

            $highestColumn = $sheet->getHighestColumn();
            if (ord($highestColumn) > ord('E')) {
                $sheet->getStyle('F' . $row . ':' . $highestColumn . $row)
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
            }
        }

        $path = 'users/' . $employee_no . '/daily-accomplishment-reports/';
        $baseFilename = 'dar-' . $todayNumeric;
        $extension = '.xlsx';
        $filename = $baseFilename . $extension;

        $counter = 1;
        while (Storage::disk('public')->exists($path . $filename)) {
            $filename = $baseFilename . '-' . $counter . $extension;
            $counter++;
        }

        $fullPath = $path . $filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        Storage::disk('public')->put($fullPath, '');
        $writer->save(storage_path('app/public/' . $fullPath));

        DB::table('accomplishment_reports')
            ->insert([
                'timelog_id' => $timelog,
                'employee_no' => $employee_no,
                'file' => $fullPath,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        return;
    }
}
