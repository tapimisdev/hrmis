<?php

namespace App\Http\Requests\Admin\Hris;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Common details (from props)
            'details' => ['required', 'array'],
            'details.employment_type_id' => ['required', 'integer', 'exists:employment_types,id'],
            'details.division_id'        => ['required', 'integer', 'exists:divisions,id'],
            'details.unit_id'            => ['required', 'integer', 'exists:units,id'],
            'details.shift_id'           => ['required', 'integer', 'exists:shifts,id'],
            'details.work_schedule_id'   => ['required', 'integer', 'exists:work_schedule,id'],

            // Employees array
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.employee_no'       => ['required', 'string', 'unique:employee_information,employee_no'],
            'employees.*.firstname'         => ['required', 'string'],
            'employees.*.middlename'        => ['nullable', 'string'],
            'employees.*.lastname'          => ['required', 'string'],
            
            'employees.*.suffix'            => ['nullable', 'string'],
            'employees.*.email'             => ['required', 'email', 'unique:users,email'],
            'employees.*.bio_id'            => ['nullable', 'string', 'unique:employee_information,biometrics_id'],
            'employees.*.date_hired'        => ['required', 'date'],
            'employees.*.isActive'          => ['required', 'string'],
            'employees.*.position'          => ['required', 'string'],
            'employees.*.tranche'           => ['required'],
            'employees.*.step' => [
                'nullable',
                'integer',
                'required_if:details.employment_type_id,' . EmploymentTypesEnum::REGULAR->value,
            ],
            'employees.*.salary_grade'      => ['required', 'string'],
            'employees.*.salary_frequency'  => ['required', 'string', 'in:twice,once'],
            'employees.*.total_salary'      => ['required', 'numeric'],
            'employees.*.daily_rate'        => ['required', 'numeric'],
            'employees.*.salary_cutoff'     => ['required_if:employees.*.salary_frequency,once', 'string', 'nullable'],
            'employees.*.deduction_on'      => ['required', 'string'],
            'employees.*.salary_method'     => ['required', 'string'],
            'employees.*.payroll_account_no'=> ['nullable', 'string'],
        ];
    }
}
