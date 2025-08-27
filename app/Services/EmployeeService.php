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
        ];

        if (!isset($tables[$type])) {
            return null;
        }

        $config = $tables[$type];
        $query = DB::table($config['table']);

        if (!empty($config['joins']) && $type === 'information') {
            $query->select(
                    'employee_information.*',
                    'divisions.id as division_id',
                    'divisions.code as division_code',
                    'divisions.name as division_name',
                    'units.id as unit_id',
                    'units.code as unit_code',
                    'units.name as unit_name',
                    'positions.id as position_id',
                    'positions.code as position_code',
                    'positions.name as position_name'
                )
                ->leftJoin('divisions', 'employee_information.division_id', '=', 'divisions.id')
                ->leftJoin('units', 'employee_information.unit_id', '=', 'units.id')
                ->leftJoin('positions', 'employee_information.position_id', '=', 'positions.id');
        }

        $query->where('employee_no', $employee_no);

        return $query->{$config['method']}();
    }



    public function getSalary(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('salary') ?? 0;
    }

    public function create_employee(bool $isNew = true, array $payload) {

        Validator::make($payload, [
            
        ]);

    }

}