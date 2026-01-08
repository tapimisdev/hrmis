<?php

namespace App\Http\Requests\Admin\Hris;

use App\Enums\EmploymentTypesEnum;
use Illuminate\Foundation\Http\FormRequest;

class UploadEmployeeRequest extends FormRequest
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
            'employment_type' => 'required|exists:employment_types,id',
            'division' => 'required|exists:divisions,id',
            'unit' => 'required|exists:units,id',
            'shift' => 'required|exists:shifts,id',
            'schedule' => 'required|exists:work_schedule,id',
            'file' => 'required|file|mimes:xlsx,xls|max:10,240',

            // Auto-generate Employee No validation (only if COS)
            'auto_generate_empno' => 'required_if:employment_type,' . EmploymentTypesEnum::COS->value . '|in:yes,no',
        ];
    }
}
