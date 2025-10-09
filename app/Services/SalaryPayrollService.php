<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\map;

class SalaryPayrollService {

    protected $daily_time_record_service;
    private $date;
    private $cutoff;

    private $eligibile;
    private $not_eligibile;

    public function __construct(DailyTimeRecordService $daily_time_record_service) 
    {
        $this->daily_time_record_service = $daily_time_record_service;
    }

    public function getPayrolls($payload)
    {
        $query = DB::table('payroll_salary');

        if (!empty($payload['year'])) {
            $query->whereYear('payroll_date', $payload['year']);
        }

        if (!empty($payload['month'])) {
            $query->whereMonth('payroll_date', $payload['month']);
        }

        if (!empty($payload['cutoff'])) {
            $query->where('cutoff', $payload['cutoff']);
        }

        if (!empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }

        // dd($query->get());

        return $query->get();
    }

    public function getEligibleEmployees($payload)
    {
        $this->date = $payload['date'];
        $this->cutoff = $payload['cutoff'];

        $employees = DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->leftJoin('positions', 'eo.position_id', '=', 'positions.id')
            ->leftJoin('divisions', 'eo.division_id', '=', 'divisions.id')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->where('eo.employment_type_id', $payload['employment_type_id'])
            ->select(
                'ep.firstname', 
                'ep.middlename', 
                'ep.lastname', 
                'ep.suffix', 

                'positions.name as position',
                'divisions.name as division',

                'ei.user_id', 
                'ei.account_status')
            ->get();
        
        
        if ($employees->isEmpty()) {
            throw new \Exception('No employees found for this employment type.', 409);
        }

        foreach ($employees as $emp) {
            $this->checkEligibility($emp);
        }

        $seperatedEmployee = [
            'eligible' => $this->eligibile,
            'not_eligible' => $this->not_eligibile,
        ];

        return $seperatedEmployee;
    }

    private function checkEligibility($employee)
    {
        // Prepare payload
        $payload = $this->getCutoff();
        $payload['user_id'] = $employee->user_id;

        $dtr = $this->daily_time_record_service->getDTR($payload);

        // Get Incomplete Logs safely
        $summary = collect($dtr['summary'])->firstWhere('label', 'Incomplete Logs');
        $incompleteLogs = isset($summary['value']) ? (int) $summary['value'] : 0;

        $remark = [];

        // Check account status
        if ($employee->account_status !== 'active') {
            $remark[] = [
                'text' => 'This Employee is Inactive',
                'url'  => route('hris.employee.information', ['id' => $employee->user_id])
            ];
        }

        // Check incomplete logs
        if ($incompleteLogs > 0) {
            $verb = ($incompleteLogs === 1) ? 'has' : 'have';
            $remark[] = [
                'text' => "This Employee {$verb} {$incompleteLogs} missing log" . ($incompleteLogs !== 1 ? 's' : ''),
                'url'  => route('daily-time-record.index', ['id' => $employee->user_id])
            ];
        }

        // Determine eligibility
        if (empty($remark)) {
            $this->eligibile[] = $employee;
        } else {
            $employee->remarks = $remark;
            $this->not_eligibile[] = $employee;
        }
    }


    private function getCutoff()
    {
        $dateObj = new \DateTime($this->date);
        $year  = $dateObj->format('Y');
        $month = $dateObj->format('m');

        if ($this->cutoff === 'first_cutoff') {
            $start = "$year-$month-01";
            $end   = "$year-$month-15";
        } elseif ($this->cutoff === 'second_cutoff') {
            $start = "$year-$month-16";
            $end   = $dateObj->format('Y-m-t'); // last day of month
        } else {
            throw new \InvalidArgumentException("Invalid cutoff type: $this->cutoff");
        }

        return [
            'startDate' => $start,
            'endDate'   => $end,
        ];
    }

}
    