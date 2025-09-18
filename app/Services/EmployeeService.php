<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\map;

class EmployeeService {
    
    public function __construct() {

    }

    public function checkIfEmployeeExists(? string $employee_no = null)
    {
        if(!is_null($employee_no)) {
            return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->exists();
        }

        return false;
    }

    public function getEmployees(?string $status, ?string $division_id, ?string $unit_id)
    {
        // Subqueries for latest rows
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
                'employee_information.date_hired',
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
            ->get();
    }

    public function getEmployee(string $type, string $employee_no)
    {
        $tables = [
            'information' => ['table' => 'employee_information', 'method' => 'first', 'joins' => true],
            'personal' => ['table' => 'employee_personal', 'method' => 'first'],
            'family' => ['table' => 'employee_family', 'method' => 'first'],
            'children' => ['table' => 'employee_children', 'method' => 'get'],
            'education' => ['table' => 'employee_education', 'method' => 'get'],
            'work-experience' => ['table' => 'employee_work_experience', 'method' => 'get'],
            'civil-service' => ['table' => 'employee_civil_service', 'method' => 'get'],
            'trainings' => ['table' => 'employee_trainings', 'method' => 'get'],
            'voluntary-works' => ['table' => 'employee_voluntary_works', 'method' => 'get'],
            'skills' => ['table' => 'employee_skills_hobbies', 'method' => 'get'],
            'account' => ['table' => 'users', 'method' => 'first'], 
        ];

        if (!isset($tables[$type])) {
            return null;
        }

        $config = $tables[$type];

        if ($type === 'account') {
            return DB::table('users')
                ->select('users.*')
                ->join('employee_information', 'employee_information.user_id', '=', 'users.id')
                ->where('employee_information.employee_no', $employee_no)
                ->first();
        }

        $query = DB::table($config['table']);

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
                    'employee_information.*',
                    'users.id as account_id',
                    'users.email as account_email',
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
                    'salary.step',
                    'salary.amount as salary',
                    'shift.shift_id',
                    'shift.work_schedule_id'
                )
                ->leftJoin('users', 'employee_information.user_id', '=', 'users.id')
                ->leftJoinSub($latestOrg, 'org', 'employee_information.employee_no', '=', 'org.employee_no')
                ->leftJoin('divisions', 'org.division_id', '=', 'divisions.id')
                ->leftJoin('units', 'org.unit_id', '=', 'units.id')
                ->leftJoin('positions', 'org.position_id', '=', 'positions.id')
                ->leftJoin('employment_types', 'org.employment_type_id', '=', 'employment_types.id')
                ->leftJoinSub($latestSalary, 'salary', 'employee_information.employee_no', '=', 'salary.employee_no')
                ->leftJoinSub($latestShift, 'shift', 'employee_information.employee_no', '=', 'shift.employee_no');
        }

        $query->where("{$config['table']}.employee_no", $employee_no);

        return $query->{$config['method']}();
    }


    public function getSalary(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('salary') ?? 0;
    }

    public function delete(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->update([
                'account_status' => 'archived',
            ]);
    }

    public function restore(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->update([
                'account_status' => 'active',
            ]);
    }

}