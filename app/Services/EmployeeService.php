<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Enums\EmploymentTypesEnum;

use function PHPSTORM_META\map;

class EmployeeService {

    # GET EMPLOYEE NUMBER BASED ON USER ID
    public function getEmployeeNo($user_id)
    {
        $employee = DB::table('employee_information')
                ->where('user_id', $user_id)
                ->select('id', 'employee_no')
                ->first();

        return $employee->employee_no;
    }

    # GET EMPLOYEE NUMBER BASED ON FULL NAME
    public function getEmployeeNoBasedOnFullName(string $name): string
    {
        $name = trim($name);

        $employee = DB::table('employee_personal')
            ->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$name}%"])
            ->first(['employee_no']);

        return $employee?->employee_no ?? 'N/A';
    }

    # GET EMPLOYEE'S USER ID BASED ON EMPLOYEE NUMBER
    public function getEmployeeUserId($employee_no)
    {
         $employee = DB::table('employee_information')
                ->where('employee_no', $employee_no)
                ->select('user_id')
                ->first();

        return $employee->user_id;
    }

    # CHECK IF EMPLOYEE NUMBER EXISTS
    public function checkIfEmployeeExists(? string $employee_no = null)
    {
        if(!is_null($employee_no)) {
            return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->exists();
        }

        return false;
    }

    # GET ALL EMPLOYEES BASED ON THE FOLLOWING
    # STATUS | DIVISION | UNIT ID | EMPLOYMENT TYPE
    public function getEmployees(?string $status, ?string $division_id, ?string $unit_id, ?string $employment_type_id)
    {
        $latestOrg = DB::table('employee_organization as eo1')
            ->select('eo1.*')
            ->whereRaw('eo1.id = (select max(eo2.id) from employee_organization eo2 where eo2.employee_no = eo1.employee_no)');

        $latestSalary = DB::table('employee_salary as es1')
            ->select('es1.*')
            ->whereRaw('es1.id = (select max(es2.id) from employee_salary es2 where es2.employee_no = es1.employee_no)');

        $latestShift = DB::table('employee_shift_work_schedule as sw1')
            ->select('sw1.*')
            ->whereRaw('sw1.id = (select max(sw2.id) from employee_shift_work_schedule sw2 where sw2.employee_no = sw1.employee_no)');

        return DB::table('employee_information')
            ->select(
                'employee_information.employee_no',
                'employee_information.biometrics_id',
                'employee_information.date_hired_organization',
                'employee_information.date_hired_company',
                'employee_information.account_status',
                'employee_information.isDeleted',
                'employee_personal.profile',
                'employee_personal.firstname',
                'employee_personal.lastname',
                

                // Organization details
                'org.id as organization_id',
                'org.effectivity_date',

                // Division
                'divisions.id as division_id',
                'divisions.code as division_code',
                'divisions.name as division_name',

                // Unit
                'units.id as unit_id',
                'units.code as unit_code',
                'units.name as unit_name',

                // Position
                'positions.id as position_id',
                'positions.code as position_code',
                'positions.name as position_name',

                // Employment type
                'employment_types.id as employment_type_id',
                'employment_types.code as employment_type_code',
                'employment_types.name as employment_type_name',

                // Salary
                'salary.amount as salary',
                'salary.salary_frequency',
                'salary.salary_basis',
                'salary.tranche_id',
                'salary.step',

                // Shift
                'shift.shift_id',
                'shift.work_schedule_id'
            )
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoinSub($latestOrg, 'org', 'employee_information.employee_no', '=', 'org.employee_no')
            ->leftJoin('divisions', 'org.division_id', '=', 'divisions.id')
            ->leftJoin('units', 'org.unit_id', '=', 'units.id')
            ->leftJoin('positions', 'org.position_id', '=', 'positions.id')
            ->leftJoin('employment_types', 'org.employment_type_id', '=', 'employment_types.id')
            ->leftJoinSub($latestSalary, 'salary', 'employee_information.employee_no', '=', 'salary.employee_no')
            ->leftJoinSub($latestShift, 'shift', 'employee_information.employee_no', '=', 'shift.employee_no')

            // Filters
            ->when($status, function ($query) use ($status) {
                return $query->where('employee_information.account_status', $status);
            })
            ->when($division_id, function ($query) use ($division_id) {
                return $query->where('org.division_id', $division_id);
            })
            ->when($unit_id, function ($query) use ($unit_id) {
                return $query->where('org.unit_id', $unit_id);
            })
            ->when($employment_type_id, function ($query) use ($employment_type_id) {
                return $query->where('org.employment_type_id', $employment_type_id);
            })
            ->get();
    }

    # GET SPECIFIC EMPLOYEES DATA USING EMPLOYEE NUMBER
    # INFORMATION | PERSONAL | FAMILY | CHILDREN | EDUCATION | WORK EXPERIENCE
    # CIVIL SERVICE | TRAININGS | VOLUNTARY WORKS | SKILLS | ACCOUNT
    public function getEmployee(string $type, string $employee_no = null, ?int $leave_id = null)
    {
        $tables = [
            'information'      => ['table' => 'employee_information', 'method' => 'first', 'joins' => true],
            'personal'         => ['table' => 'employee_personal', 'method' => 'first'],
            'family'           => ['table' => 'employee_family', 'method' => 'first'],
            'children'         => ['table' => 'employee_children', 'method' => 'get'],
            'education'        => ['table' => 'employee_education', 'method' => 'get'],
            'work-experience'  => ['table' => 'employee_work_experience', 'method' => 'get'],
            'civil-service'    => ['table' => 'employee_civil_service', 'method' => 'get'],
            'trainings'        => ['table' => 'employee_trainings', 'method' => 'get'],
            'voluntary-works'  => ['table' => 'employee_voluntary_works', 'method' => 'get'],
            'skills'           => ['table' => 'employee_skills_hobbies', 'method' => 'get'],
            'account'          => ['table' => 'users', 'method' => 'first'],
        ];

        if (!isset($tables[$type])) {
            return null;
        }

        $config = $tables[$type];

        /**
         * ACCOUNT BASIC LOOKUP
         */
        if ($type === 'account') {
            return DB::table('users')
                ->select('users.*')
                ->join('employee_information', 'employee_information.user_id', '=', 'users.id')
                ->where('employee_information.employee_no', $employee_no)
                ->first();
        }


        /**
         * DEFAULT QUERY BUILDER
         */
        $query = DB::table($config['table']);

        /**
         * INFORMATION JOINS
         */
        if (!empty($config['joins']) && $type === 'information') {

            $latestOrg = DB::table('employee_organization as eo1')
                ->select('eo1.*')
                ->whereRaw('eo1.id = (select max(eo2.id) from employee_organization eo2 where eo2.employee_no = eo1.employee_no)');

            $latestSalary = DB::table('employee_salary as es1')
                ->select('es1.*')
                ->whereRaw('es1.id = (select max(es2.id) from employee_salary es2 where es2.employee_no = es1.employee_no)');

            $latestShift = DB::table('employee_shift_work_schedule as sw1')
                ->select('sw1.*')
                ->whereRaw('sw1.id = (select max(sw2.id) from employee_shift_work_schedule sw2 where sw2.employee_no = sw1.employee_no)');

            $query->select(
                    'employee_information.employee_no',
                    'employee_information.date_hired_organization',
                    'employee_information.date_hired_company',
                    'employee_information.biometrics_id',
                    'employee_information.account_status',
                    'employee_information.isDeleted',
                    'employee_information.toUpdatePassword',
                    'employee_information.two_percent',
                    'employee_information.three_percent',
                    'employee_information.five_percent',

                    'employee_personal.profile',
                    'employee_personal.firstname',
                    'employee_personal.lastname',

                    'org.id as organization_id',
                    'org.effectivity_date',

                    'divisions.id as division_id',
                    'divisions.code as division_code',
                    'divisions.name as division_name',

                    'units.id as unit_id',
                    'units.code as unit_code',
                    'units.name as unit_name',

                    'positions.id as position_id',
                    'positions.code as position_code',
                    'positions.name as position_name',

                    'employment_types.id as employment_type_id',
                    'employment_types.code as employment_type_code',
                    'employment_types.name as employment_type_name',

                    'salary.tranche_id',
                    'salary.salary_grade',
                    'salary.step',
                    'salary.salary_frequency',
                    'salary.salary_cutoff',
                    'salary.deduction_applied',
                    'salary.salary_basis',
                    'salary.amount as salary',
                    'salary.daily_rate',
                    'salary.salary_method',
                    'salary.effectivity_date',

                    'shift.shift_id',
                    'shift.work_schedule_id'
                )
                ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
                ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
                ->leftJoinSub($latestOrg, 'org', 'employee_information.employee_no', '=', 'org.employee_no')
                ->leftJoin('divisions', 'org.division_id', '=', 'divisions.id')
                ->leftJoin('units', 'org.unit_id', '=', 'units.id')
                ->leftJoin('positions', 'org.position_id', '=', 'positions.id')
                ->leftJoin('employment_types', 'org.employment_type_id', '=', 'employment_types.id')
                ->leftJoinSub($latestSalary, 'salary', 'employee_information.employee_no', '=', 'salary.employee_no')
                ->leftJoinSub($latestShift, 'shift', 'employee_information.employee_no', '=', 'shift.employee_no');
        }

        /**
         * DEFAULT FILTER
         */
        $query->where("{$config['table']}.employee_no", $employee_no);

        return $query->{$config['method']}();
    }

     # GENERATE EMPLOYEE NO BASED ON DATE HIRED FOR COS EMPLOYEES
    public function generateEmployeeNo($dateHired)
    {
        return DB::transaction(function () use ($dateHired) {

            $date = \Carbon\Carbon::parse($dateHired);
            $year = $date->format('Y');
            $semester = ($date->month <= 6) ? 1 : 2;

            do {
                $lastEmployee = DB::table('employee_information')
                    ->whereYear('date_hired_company', $year)
                    ->whereRaw(
                        'CASE WHEN MONTH(date_hired_company) <= 6 THEN 1 ELSE 2 END = ?',
                        [$semester]
                    )
                    ->lockForUpdate()
                    ->orderByDesc('employee_no')
                    ->first();

                if ($lastEmployee && preg_match('/(\d{4})(\d{1})-(\d+)/', $lastEmployee->employee_no, $matches)) {
                    $sequence = (int) $matches[3] + 1;
                } else {
                    $sequence = 1;
                }

                // YYYYSS-XX
                $employeeNo = "{$year}{$semester}-" . str_pad($sequence, 2, '0', STR_PAD_LEFT);

            } while (
                DB::table('employee_information')->where('employee_no', $employeeNo)->exists()
            );

            return $employeeNo;
        });
    }
    
    # GET ALL ACTIVE EMPLOYEES
    public function getAllActiveEmployee($employment_type_id)
    {
        return DB::table('employee_information as ei')
            ->leftJoin('employee_organization as eo', 'ei.employee_no', '=', 'eo.employee_no')
            ->where('eo.employment_type_id', $employment_type_id)
            ->where('ei.account_status', 'active')
            ->pluck('ei.employee_no')
            ->toArray();
    }

    # GET ONLY THE REGULAR EMPLOYEES
    public function getRegularEmployees()
    {

        $latestOrgSub = DB::table('employee_organization as eo1')
            ->selectRaw('MAX(eo1.id) as latest_id, eo1.employee_no')
            ->groupBy('eo1.employee_no');

        $data = DB::table('employee_information as ei')
            ->leftJoin('employee_personal as ep', 'ei.employee_no', '=', 'ep.employee_no')
            ->leftJoinSub($latestOrgSub, 'latest_org', function ($join) {
                $join->on('ei.employee_no', '=', 'latest_org.employee_no');
            })
            ->leftJoin('employee_organization as eo', 'eo.id', '=', 'latest_org.latest_id')
            ->where('eo.employment_type_id', EmploymentTypesEnum::REGULAR->value)
            ->select(
                'ei.employee_no',
                'ei.biometrics_id', 
                'ep.firstname', 
                'ep.lastname')
            ->get();

        return $data;
    }

    # GET EMPLOYEE'S SALARY HISTORY
    public function getSalaryHistory(string $employee_no) {
        $data = DB::table('employee_salary')
            ->where('employee_no', $employee_no)
            ->orderBy('effectivity_date', 'desc')
            ->get();

        return $data;
    }

    # SET ARCHIVED OR SOFT DELETE EMPLOYEE'S ACCOUNT
    public function delete(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->update([
                'account_status' => 'archived',
            ]);
    }

    # RESTORE ARCHIVED ACCOUNT USER
    public function restore(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->update([
                'account_status' => 'active',
            ]);
    }

    # GET ALL LEAVE TYPES FOR EVERY EMPLOYEE NO
    public function getLeaveTypes(string $employee_no, array $showCreditsESS = [true])
    {
        $employment_type_id = DB::table('employee_organization')
            ->where('employee_no', $employee_no)
            ->latest('id')
            ->value('employment_type_id');

        $regular_id = EmploymentTypesEnum::REGULAR->value;

        if ($employment_type_id == $regular_id) {

            $latestCredits = DB::table('leave_credits as lc')
                ->select('lc.*')
                ->where('lc.employee_no', $employee_no)
                ->whereRaw('lc.as_of = (
                    SELECT MAX(lc2.as_of)
                    FROM leave_credits AS lc2
                    WHERE lc2.employee_no = lc.employee_no
                    AND lc2.leave_id = lc.leave_id
                )');

            $data = DB::table('leaves as l')
                ->leftJoinSub($latestCredits, 'lc', function ($join) {
                    $join->on('l.id', '=', 'lc.leave_id');
                })
                ->select(
                    DB::raw('COALESCE(lc.id, 0) as id'),
                    DB::raw('COALESCE(lc.employee_no, "") as employee_no'),
                    DB::raw('COALESCE(lc.balance, 0) as balance'),
                    DB::raw('COALESCE(lc.as_of, NULL) as as_of'),
                    'l.name',
                    'l.id as leave_id',
                    'l.is_cumulative',
                    'l.is_active',
                    DB::raw("
                        CASE WHEN EXISTS (
                            SELECT 1 FROM leave_credits lc_check
                            WHERE lc_check.employee_no = '$employee_no'
                            LIMIT 1
                        )
                        THEN true ELSE false END as hasLeaveCredit
                    ")
                )
                ->groupBy(
                    'l.id',
                    'l.name',
                    'l.is_cumulative',
                    'l.is_active',
                    'lc.id',
                    'lc.employee_no',
                    'lc.balance',
                    'lc.as_of'
                )
                ->where('l.is_active', true)
                ->orderByDesc('l.is_cumulative')
                ->whereIn('showCreditsESS', $showCreditsESS)
                ->get();

            return [
                'status' => 'eligible',
                'data' => $data
            ];
        }

        return [
            'status' => 'ineligible',
            'data' => null
        ];
    }

    # GET EMPLOYEE'S LEAVE CREDITS 
    # BASED ON LEAVE ID
    public function checkLeaveCredits(string $employee_no, int $leave_id)
    {
        $leaveCredit = DB::table('leave_credits')
            ->where('employee_no', $employee_no)
            ->where('leave_id', $leave_id)
            ->latest('as_of')
            ->first();

        return $leaveCredit;
    }

    # GET LEAVE TYPE SETTINGS
    # BASED ON LEAVE ID
    public function getLeaveSettings(int $leave_id) {
        return DB::table('leaves_settings')
            ->where('leave_id', $leave_id)
            ->first();
    }

    # GET EMPLOYEE'S LEAVE APPLICATIONS 
    # BY ID
    public function getLeave(int $leave_id) {
        return DB::table('leave_applications')
            ->where('id', $leave_id)
            ->first();
    }

    # GET LEAVE TYPE INFORMATION DETAILS
    public function getLeaveInfo($id) {
        return DB::table('leaves')
            ->where('id', $id)
            ->first();
    }

    # GET EMPLOYEE'S LEAVE CREDITS
    public function getOffsetCredits(string $employee_no, bool $isLatest = false) {
        
        $data = DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->orderByDesc('as_of');  

        if($isLatest) {
            return $data->first();
        }

        return $data->get();
    }

    # GET EMPLOYEE'S OFFSET CREDITS BY MONTH YEAR
    public function getOffsetCreditsByMonthYear(string $employee_no, string $monthYear)
    {
        // Current month record
        $current = DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->where('as_of', $monthYear)
            ->first();

        // Previous balance (latest before selected month)
        $previousBalance = DB::table('offset_credits')
            ->where('employee_no', $employee_no)
            ->where('as_of', '<', $monthYear)
            ->orderBy('as_of', 'desc')
            ->value('balance'); // gets only the balance column

        return [
            'current' => $current,
            'previous_balance' => $previousBalance ?? 0
        ];
    }

    # GET EMPLOYEE'S LEAVE CREDITS
    public function getLeaveCredits(string $employee_no, int $leave_id, bool $isLatest = false) {
        
        $data = DB::table('leave_credits')
            ->where('leave_id', $leave_id)
            ->where('employee_no', $employee_no)
            ->orderByDesc('as_of');  

        if($isLatest) {
            return $data->first();
        }

        return $data->get();

    }

    # GET EMPLOYEE'S LEAVE CREDITS BY MONTH YEAR
    public function getLeaveCreditsByMonthYear(string $employee_no, int $leave_id, string $monthYear)
    {
        $current = DB::table('leave_credits')
            ->where('leave_id', $leave_id)
            ->where('employee_no', $employee_no)
            ->where('as_of', $monthYear)
            ->first();

        $previousBalance = DB::table('leave_credits')
            ->where('leave_id', $leave_id)
            ->where('employee_no', $employee_no)
            ->where('as_of', '<', $monthYear)
            ->orderBy('as_of', 'desc')
            ->value('balance'); 

        return [
            'current' => $current,
            'previous_balance' => $previousBalance ?? 0
        ];
    }

}