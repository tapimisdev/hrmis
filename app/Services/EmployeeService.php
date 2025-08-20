<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmployeeService {
    
    public function __construct() {

    }

    public function getEmployee(?string $employee_no = null) {
        $query = DB::table('employee_information')
            ->leftJoin('employee_personal', 'employee_information.employee_no', '=', 'employee_personal.employee_no')
            ->leftJoin('employee_parents', 'employee_information.employee_no', '=', 'employee_parents.employee_no')
            ->leftJoin('employee_children', 'employee_information.employee_no', '=', 'employee_children.employee_no')
            ->leftJoin('employee_education', 'employee_information.employee_no', '=', 'employee_education.employee_no')
            ->leftJoin('employee_employment_history', 'employee_information.employee_no', '=', 'employee_employment_history.employee_no')
            ->leftJoin('employee_civil_service', 'employee_information.employee_no', '=', 'employee_civil_service.employee_no')
            ->leftJoin('employee_trainings', 'employee_information.employee_no', '=', 'employee_trainings.employee_no')
            ->leftJoin('employee_other_works', 'employee_information.employee_no', '=', 'employee_other_works.employee_no')
            ->leftJoin('employee_skills_hobbies', 'employee_information.employee_no', '=', 'employee_skills_hobbies.employee_no');

        if (is_null($employee_no)) {
            return $query->get();
        }

        return $query->where('employee_information.employee_no', $employee_no)->first();
    }

    public function getSalary(string $employee_no) {
        return DB::table('employee_information')
            ->where('employee_no', $employee_no)
            ->value('salary') ?? 0;
    }

}